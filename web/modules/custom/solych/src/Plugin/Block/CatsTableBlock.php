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
   * The limit of records to display.
   *
   * @var int|null
   */
  protected $limit = NULL;

  /**
   * Sets the limit for the number of records to display.
   *
   * @param int|null $limit
   *   The maximum number of records to display. NULL for no limit.
   */
  public function setLimit($limit = NULL) {
    $this->limit = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    \Drupal::logger('cats_table_block')->notice('Table limit value: @limit', [
      '@limit' => $this->limit === NULL ? 'No limit' : $this->limit,
    ]);
    $header = [
      ['data' => $this->t('Cat\'s name')],
      ['data' => $this->t('Owner email')],
      ['data' => $this->t('Photo')],
      ['data' => $this->t('Added date')]
    ];

    $connection = Database::getConnection();
    if ($this->limit !== NULL) {
      $query = $connection->select('solych', 's')
      ->fields('s', ['cat_name', 'email', 'photo', 'created'])
      ->orderBy('created', 'DESC')->range(0, $this->limit)->execute();
    } else {
      $query = $connection->select('solych', 's')
        ->fields('s', ['cat_name', 'email', 'photo', 'created'])
        ->orderBy('created', 'DESC')->execute();
    }

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
