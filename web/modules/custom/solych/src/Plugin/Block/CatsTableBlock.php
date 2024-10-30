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

    $cats = [];

    foreach ($query as $record) {
      $photo_link = '';

      if ($record->photo) {
        $file = File::load($record->photo);

        $photo_link = $file ? \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()) : '';
      }

      $created_date = \Drupal::service('date.formatter')->format($record->created, 'custom', 'd-m-Y H:i:s');

      $cats[] = [
        'cat_name' => $record->cat_name,
        'email' => $record->email,
        'photo' => $photo_link,
        'created_date' => $created_date
      ];
    }
    return [
      '#theme' => 'cats_table',
      '#cats' => $cats,
      '#attached' => [
        'library' => [
          'solych/modal',
        ],
      ],
    ];
  }

}
