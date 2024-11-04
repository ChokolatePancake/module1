<?php

namespace Drupal\solych\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing cat and owner information in a modal.
 */
class CatsEditForm extends CatsForm {

  /**
   * The ID of the cat record to edit.
   *
   * @var int
   */
  protected $catId;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a CatsEditForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct($messenger,
                              $block_manager,
                              $email_validator,
                              Connection $database,
                              FileUrlGenerator $file_url_generator, $cache_invalidator) {
    parent::__construct($messenger, $block_manager, $email_validator, $database, $cache_invalidator);
    $this->fileUrlGenerator = $file_url_generator;
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
      $container->get('file_url_generator'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solych_cats_edit_form';
  }

  /**
   * Builds the edit form with preloaded data for editing a specific record.
   *
   * @param array $form
   *   The form array to populate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int|null $cat_id
   *   The ID of the record being edited.
   * @param string|null $referer
   *   The referer URL to redirect after saving, if available.
   *
   * @return array
   *   The form array with populated data.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cat_id = NULL, $referer = NULL) {
    $this->catId = $cat_id;
    $form = parent::buildForm($form, $form_state);

    unset($form['cats_table']);

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $cat_id,
    ];

    $form['referer'] = [
      '#type' => 'hidden',
      '#value' => $referer,
    ];

    if ($cat_id) {
      $record = $this->loadCatRecord($cat_id);
      $form['cat_name']['#default_value'] = $record['cat_name'];
      $form['email']['#default_value'] = $record['email'];
      $form['photo']['#default_value'] = $record['photo'] ? [$record['photo']] : [];
    }

    $form['cat_name']['#ajax']['wrapper'] = 'edit-cat-name-validation-message';
    $form['email']['#ajax']['wrapper'] = 'edit-email-validation-message';
    $form['photo']['#ajax']['wrapper'] = 'edit-photo-validation-message';

    if (!empty($record['photo'])) {
      $file = File::load($record['photo']);
      if ($file) {
        $file_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        $form['#attached']['drupalSettings']['solych']['photoPreviewUrl'] = $file_url;      }
    }

    $form['cat_name_validation_message']['#attributes']['id'] = 'edit-cat-name-validation-message';
    $form['email_validation_message']['#attributes']['id'] = 'edit-email-validation-message';
    $form['photo_validation_message']['#attributes']['id'] = 'edit-photo-validation-message';

    $form['actions']['submit']['#value'] = $this->t('Save');
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmit',
      'wrapper' => 'edit-form-messages-wrapper',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
      '#ajax' => [
        'callback' => '::closeModal',
        'event' => 'click'
      ],
    ];

    $form['#prefix'] = '<div id="edit-form-messages-wrapper">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Loads the cat record from the database.
   *
   * @param int $cat_id
   *   The ID of the record to load.
   *
   * @return array|null
   *   The record data, or NULL if not found.
   */
  protected function loadCatRecord($cat_id) {
    return $this->database->select('solych', 's')
      ->fields('s', ['cat_name', 'email', 'photo'])
      ->condition('id', $cat_id)
      ->execute()
      ->fetchAssoc() ?: NULL;
  }

  /**
   * Closes the modal without saving.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   */
  public function closeModal(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * AJAX submission handler for saving the form data.
   * @param array &$form
   *   An associative array containing the form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object containing commands to update the page.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      return $form;
    }
    $cat_name = $form_state->getValue('cat_name');
    $email = $form_state->getValue('email');
    $new_photo = isset($form_state->getValue('photo')[0]) ? $form_state->getValue('photo')[0] : NULL;

    $existing_record = $this->loadCatRecord($this->catId);
    $existing_photo = $existing_record['photo'] ?? NULL;

    if ($new_photo && $new_photo != $existing_photo) {
      if ($existing_photo) {
        $old_file = File::load($existing_photo);
        if ($old_file) {
          $old_file->delete();
        }
      }

      $file = File::load($new_photo);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    } elseif ($new_photo == $existing_photo) {
      $new_photo = $existing_photo;
    }

    if ($this->catId) {
      $this->database->update('solych')
        ->fields([
          'cat_name' => $cat_name,
          'email' => $email,
          'photo' => $new_photo,
        ])
        ->condition('id', $this->catId)
        ->execute();
    }
    $response->addCommand(new CloseModalDialogCommand());

    $referer = $form_state->getValue('referer');
    if ($referer) {
      $response->addCommand(new RedirectCommand($referer));
    } else {
      $response->addCommand(new RedirectCommand(Url::fromRoute('solych.cats_list')->toString()));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handles by AJAX
  }
}
