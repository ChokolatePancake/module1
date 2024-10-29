<?php

namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides a page with a table of all cats.
 */
class CatsListController extends ControllerBase {

  /**
   * Displays the table with a button link to add cat form.
   *
   * @return array
   *   A render array containing the table and a back button.
   */
  public function content() {
    $table_block = \Drupal::service('plugin.manager.block')->createInstance('cats_table_block');
    $table = $table_block->build();

    $form_button = [
      '#type' => 'link',
      '#title' => $this->t('Add cat'),
      '#url' =>Url::fromRoute('solych.cat_add'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return [
      '#theme' => 'cats_list_page',
      '#title' => $this->t('Cats list'),
      '#form_button' => $form_button,
      '#table' => $table,
    ];
  }

}
