<?php

namespace Drupal\solych\Form;

use Drupal\Core\Ajax\AjaxResponse;
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
   * Constructor the CatsForm class.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service displays messages after submit
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    $this->validator = new CatsFormValidator();
    $this->response = new AjaxResponse();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solych_cats_form';
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
    ];

    $form['cat_name_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'cat-name-validation-message'],
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
      '#description' => $this->t('Please enter a valid email(only latin letters, numbers, underscores or hyphens).'),
    ];

    $form['email_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'email-validation-message'],
    ];

    $form['photo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Your cat\'s photo:'),
      '#required' => TRUE,
      '#description' => $this->t('Allowed formats: jpeg, jpg or png(max size:2MB)'),
      '#description_display' => 'before',
      '#validators' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png'],
        'max_size' => [2 * 1024 * 1024],
      ],
    ];

    $form['photo_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'photo-validation-message'],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'cats-form-messages',
        'effect' => 'fade',
      ],
      '#attributes' => ['class' => ['submit-cats-form']]
    ];

    $form['#prefix'] = '<div id="cats-form-messages">';

    $form['#suffix'] = '</div>';

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
    $is_valid = \Drupal::service('email.validator')->isValid($email);
    $error_message = $is_valid ? '' : $this->t('The email address is not valid.');

    return $this->validator->handleValidationAjax('#email-validation-message', $error_message);
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

    return $this->validator->handleValidationAjax('#cat-name-validation-message', $error_message);
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
      $file = \Drupal\file\Entity\File::load($file_id);

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

    $this->messenger->addMessage($this->t('We are glad to see your cat @cat_name!', ['@cat_name' => $cat_name]));

    $form_state->setRebuild(TRUE);
    $form['cat_name']['#value'] = '';
    $form['email']['#value'] = '';
    $form['photo']['#value'] = '';
    return $form;
  }

}
