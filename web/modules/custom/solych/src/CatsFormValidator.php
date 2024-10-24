<?php

namespace Drupal\solych;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;

class CatsFormValidator {

  /**
   * Validates the email address.
   *
   * @param string $email
   *   The email address to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string|null
   *   An error message if the validation fails, or NULL if the email is valid.
   */
  public function validateEmail($email, FormStateInterface $form_state) {
    $parts = explode('@', $email);

    if (count($parts) != 2) {
      return t('The email address must contain exactly one "@" symbol.');
    }

    $first_part = $parts[0];
    $first_pattern = '/^[a-zA-Z0-9_\-.]+$/';

    if (!preg_match($first_pattern, $first_part)) {
      return t('The first part of the email (before the "@") can only contain Latin letters, numbers, underscores (_), hyphens (-), and dots (.).');
    }

    $domain_part = '@' . $parts[1];
    $domain_pattern = '/@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,}$/';

    if (!preg_match($domain_pattern, $domain_part)) {
      return t('The domain part of the email (after the "@") is not valid. It should be in the format "example.com".');
    }
    return NULL;
  }

/**
 * Validates the cat's name.
 *
 * @param string $name
 *   The name to validate.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @return string|null
 *   An error message if the validation fails, or NULL if the email is valid.
 */
  public function validateName(string $name, FormStateInterface $form_state) {
    if (mb_strlen($name) < 2 || mb_strlen($name) > 32) {
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
