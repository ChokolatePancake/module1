<?php

namespace Drupal\solych\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

class CatsForm extends FormBase {

  // Property to hold the Messenger service.
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
   * Gets the unique ID of the form.
   *
   * @return string
   *   The unique ID for this form.
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
    ];

    return $form;
  }

  /**
   * Handles form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cat_name = $form_state->getValue('cat_name');

    $this->messenger->addMessage($this->t('We are glad to see your cat @cat_name!', ['@cat_name' => $cat_name]));
  }

  /**
   * Static method to create an instance of the class and inject services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container that holds all available services.
   *
   * @return static
   *   An instance of the class that handles the form.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }
}
