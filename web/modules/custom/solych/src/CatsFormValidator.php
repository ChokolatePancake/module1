<?php

namespace Drupal\solych;

use Drupal\Core\Form\FormStateInterface;

class CatsFormValidator {

  /**
   * Validates the Cats form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm($form_id, array $form, FormStateInterface $form_state,) {
    $cat_name = $form_state->getValue('cat_name');

    if (mb_strlen($cat_name) < 2 || mb_strlen($cat_name) > 32) {
      $form_state->setErrorByName('cat_name', t('The cat name must be between 2 and 32 characters.'));
    }
  }
}
