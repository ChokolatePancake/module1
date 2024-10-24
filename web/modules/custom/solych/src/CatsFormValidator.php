<?php

namespace Drupal\solych;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form validation services for the Cats form.
 *
 * This class contains validation logic for email addresses, cat names, and
 * reusable AJAX response handling for form validation feedback.
 */
class CatsFormValidator {

/**
 * Validates the cat's name.
 *
 * @param string $cat_name
 *   The cat name to validate.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @return string|null
 *   An error message if the validation fails, or NULL if the email is valid.
 */
  public function validateCatName(string $cat_name, FormStateInterface $form_state) {
    if (mb_strlen($cat_name) < 2 || mb_strlen($cat_name) > 32) {
      return t('The cat name must be between 2 and 32 characters.');
    }
    return NULL;
  }

  /**
   * Reusable method for handling AJAX validation.
   *
   * @param string $selector
   *   The jQuery selector for the element to update.
   * @param string $message
   *   The validation message.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response with commands.
   */
  public function handleValidationAjax($selector, $message) {
    $response = new AjaxResponse();
    if ($message) {
      $response->addCommand(new InvokeCommand($selector, 'addClass', ['hint']));
    }
    else {
      $response->addCommand(new InvokeCommand($selector, 'removeClass', ['hint']));
    }

    $response->addCommand(new InvokeCommand($selector, 'html', [$message]));

    return $response;
  }
}
