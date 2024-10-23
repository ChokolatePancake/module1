<?php

namespace Drupal\solych\Form;

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
   * Constructor for injection Messenger service.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service displays messages after submit
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
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
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'cats-form-messages',
        'effect' => 'fade',
      ]
    ];

    $form['#prefix'] = '<div id="cats-form-messages">';

    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Create an instance of the validator and validate the form.
    $validator = new CatsFormValidator();
    $validator->validateForm($this->getFormId(), $form, $form_state);
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
