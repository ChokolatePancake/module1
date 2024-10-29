<?php

namespace Drupal\solych\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'CatsTableBlock' block to display a table of cat records.
 *
 * @Block(
 *   id = "cats_table_block",
 *   admin_label = @Translation("Cats Table Block"),
 * )
 */
class CatsTableBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $header = [
      ['data' => $this->t('Cat\'s name')],
      ['data' => $this->t('Owner email')],
      ['data' => $this->t('Photo')],
      ['data' => $this->t('Added date')]
    ];

    $connection = Database::getConnection();
    $query = $connection->select('solych', 's')
      ->fields('s', ['cat_name', 'email', 'photo', 'created'])
      ->orderBy('created', 'DESC')
      ->execute();

    $rows = [];

    foreach ($query as $record) {
      $photo_link = '';

      if ($record->photo) {
        $file = File::load($record->photo);

        if ($file) {
          $photo_link = [
            '#theme' => 'image',
            '#uri' => $file->getFileUri(),
            '#alt' => $this->t('Photo of @cat_name', ['@cat_name' => $record->cat_name]),
            '#width' => 100,
            '#height' => 100,
          ];
        }
      }

      $created_date = date('Y-m-d H:i', $record->created);

      $rows[] = [
        'data' => [
          $record->cat_name,
          $record->email,
          ['data' => $photo_link, 'align' => 'center'],
          $created_date
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No records found.'),
    ];
  }

}
