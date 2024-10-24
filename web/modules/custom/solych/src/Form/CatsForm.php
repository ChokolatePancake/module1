<?php

namespace Drupal\solych\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\solych\CatsFormValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

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
   * Constructor for injection Messenger service.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service displays messages after submit
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    $this->validator = new CatsFormValidator();
    $response = new AjaxResponse();
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
        'callback' => '::validateNameAjax',
        'event' => 'change',
        'wrapper' => 'name-validation-message',
      ],
    ];

    $form['name_validation_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'name-validation-message'],
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
    $email = $form_state->getValue('email');
    $error_message = $this->validator->validateEmail($email, $form_state);

    return $this->validator->handleValidationAjax('#email-validation-message', $error_message);
  }

  /**
   * AJAX callback for real-time name validation.
   *
   * @param array &$form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response object to manipulate the page.
   */
  public function validateNameAjax(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('cat_name');
    $error_message = $this->validator->validateName($name, $form_state);

    return $this->validator->handleValidationAjax('#name-validation-message', $error_message);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('cat_name');

    $email = $form_state->getValue('email');

    $error_message = $this->validator->validateName($name, $form_state);

    if ($error_message) {
      $form_state->setErrorByName('name', $error_message);
    }

    $error_message = $this->validator->validateEmail($email, $form_state);

    if ($error_message) {
      $form_state->setErrorByName('email', $error_message);
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
    );
  }
}
