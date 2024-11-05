<?php

namespace Drupal\solych\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\solych\CatsFormValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides a form for adding cat and owner information with AJAX validation.
 *
 * This form allows users to submit their cat's name and email address, with
 * real-time validation through AJAX. It uses a custom form validator and
 * displays success or error messages using the Messenger service.
 */
class CatsForm extends FormBase {

  /**
   * The Messenger service for displaying messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The custom form validator.
   *
   * @var \Drupal\solych\CatsFormValidator
   */
  protected $validator;

  /**
   * The AjaxResponse object for handling AJAX commands.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $response;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs the CatsForm.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The file URL generator service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(MessengerInterface $messenger,
                              BlockManagerInterface $block_manager,
                              EmailValidatorInterface $email_validator,
                              Connection $database) {
    $this->messenger = $messenger;
    $this->validator = new CatsFormValidator();
    $this->response = new AjaxResponse();
    $this->blockManager = $block_manager;
    $this->emailValidator = $email_validator;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.manager.block'),
      $container->get('email.validator'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solych_cats_form';
  }

  protected function loadCatTableBlock($limit) {
    $table_block = $this->blockManager->createInstance('cats_table_block', []);
    $table_block->setLimit($limit);
    return $table_block->build();
  }

  /**
   * Builds the form elements.
   *
   * @param array $form
   *   The form array to populate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with added elements.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['autocomplete'] = 'off';

    $form['#cache'] = [
      'tags' => ['cats_form_data'],
    ];

    $form['cat_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('Minimal length of name:2 characters. Maximal length of name:32 characters.'),
      '#ajax' => [
        'callback' => '::validateCatNameAjax',
        'event' => 'change',
        'wrapper' => 'cat-name-validation-message',
      ],
      '#weight' => -10,
    ];

    $form['cat_name_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'cat-name-validation-message'],
      '#weight' => -9,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your Email:'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::validateEmailAjax',
        'event' => 'change',
        'wrapper' => 'email-validation-message',
      ],
      '#description' => $this->t(
        'Please enter a valid email(only latin letters, numbers, underscores or hyphens).'
      ),
      '#weight' => -8,
    ];

    $form['email_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'email-validation-message'],
      '#weight' => -7,
    ];

    $form['photo_preview'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'photo-preview'],
      '#weight' => -6,
    ];
    $form['photo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Your cat\'s photo:'),
      '#required' => TRUE,
      '#description' => $this->t('Allowed formats: jpeg, jpg or png(max size:2MB)'),
      '#description_display' => 'before',
      '#upload_location' => 'public://cat_photos/',
      '#validators' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png'],
        'max_size' => [2 * 1024 * 1024],
      ],
      '#weight' => -5,
    ];

    $form['photo_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'photo-validation-message'],
      '#weight' => -4,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => -3,
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'cats-form-messages',
        'effect' => 'fade',
      ],
      '#attributes' => ['class' => ['submit-cats-form']],
    ];

    $form['#prefix'] = '<div id="cats-form-messages">';

    $form['#suffix'] = '</div>';

    $form['table_wrapper'] = [
      '#type' => 'container',
      '#weight' => 10,
      'cats_table' => $this->loadCatTableBlock(5),
    ];

    $form['#attached']['library'][] = 'solych/photo_preview';

    return $form;
  }

  /**
   * AJAX callback for real-time email validation.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response object to manipulate the page.
   */
  public function validateEmailAjax(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();

    $email = $form_state->getValue('email');
    $is_valid = $this->emailValidator->isValid($email);
    $error_message = $is_valid ? '' : $this->t('The email address is not valid.');

    return $this->validator->handleValidationAjax("#{$form['email']['#ajax']['wrapper']}", $error_message);
  }

  /**
   * AJAX callback for real-time cat name validation.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response object to manipulate the page.
   */
  public function validateCatNameAjax(array &$form, FormStateInterface $form_state) {
    $cat_name = $form_state->getValue('cat_name');
    $error_message = $this->validator->validateCatName($cat_name, $form_state);
    return $this->validator->handleValidationAjax("#{$form['cat_name']['#ajax']['wrapper']}", $error_message);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cat_name = $form_state->getValue('cat_name');

    $error_message = $this->validator->validateCatName($cat_name, $form_state);

    if ($error_message) {
      $form_state->setErrorByName('cat_name', $error_message);
    }

    $file_id = $form_state->getValue('photo')[0] ?? NULL;
    $triggering_element = $form_state->getTriggeringElement();
    $is_submit = $triggering_element['#name'];

    if ($file_id) {
      $file = File::load($file_id);

      $validators = $form['photo']['#validators'];
      $error_message = $this->validator->validatePhoto($file, $validators);

      if ($error_message) {
        if ($is_submit === 'photo_remove_button') {
          $this->messenger->addWarning(t('Don\'t forget to upload photo'));
        } elseif ($is_submit !== 'photo_upload_button') {
          $form_state->setErrorByName('photo', $error_message);
          $form_state->setValue('photo', []);
          $file->delete();
        } else {
          $this->messenger->addWarning($error_message);
        }
      }
    } else {
      $form_state->setErrorByName('photo', $this->t('Please upload a photo.'));
    }
  }


  /**
   * Handles default form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handles by AJAX
  }

  /**
   * Handles AJAX form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array with the updated form.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    $cat_name = $form_state->getValue('cat_name');

    $this->messenger->addMessage(
      $this->t('We are glad to see your cat @cat_name!',
        ['@cat_name' => $cat_name]));

    $email = $form_state->getValue('email');
    $created = time();
    $file_id = $form_state->getValue('photo')[0];

    if ($file_id) {
      $file = File::load($file_id);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    $this->database->insert('solych')
      ->fields([
        'cat_name' => $cat_name,
        'email' => $email,
        'photo' => $file_id,
        'created' => $created,
      ])
      ->execute();

    $form_state->setValues([]);
    $form_state->setUserInput([]);
    $form_state->setRebuild(TRUE);
    $form_state->setValue('cat_name', NULL);
    $form_state->setValue('email', NULL);
    $form_state->setValue('photo', NULL);
    $form['cat_name']['#value'] = '';
    $form['email']['#value'] = '';
    $form['photo']['#value'] = '';
    $form['table_wrapper']['cats_table'] = $this->loadCatTableBlock(5);
    return $form;
  }

}
