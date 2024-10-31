<?php

namespace Drupal\solych\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CatsTableBlock' block to display a table of cat records.
 *
 * @Block(
 *   id = "cats_table_block",
 *   admin_label = @Translation("Cats Table Block"),
 * )
 */
class CatsTableBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The limit of records to display.
   *
   * @var int|null
   */
  protected ?int $limit = NULL;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;


  /**
   * Constructs a new CatsTableBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              Connection $database,
                              FileUrlGenerator $file_url_generator,
                              DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->fileUrlGenerator = $file_url_generator;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('file_url_generator'),
      $container->get('date.formatter')
    );
  }

  /**
   * Sets the limit for the number of records to display.
   *
   * @param int|null $limit
   *   The maximum number of records to display. NULL for no limit.
   */
  public function setLimit(int $limit = NULL) {
    $this->limit = $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = $this->database->select('solych', 's')
      ->fields('s', ['cat_name', 'email', 'photo', 'created'])
      ->orderBy('created', 'DESC');
    if ($this->limit !== NULL) {
      $query->range(0, $this->limit);
    }
    $result = $query->execute();

    $cats = [];

    foreach ($result as $record) {
      $photo_link = '';

      if ($record->photo) {
        $file = File::load($record->photo);

        $photo_link = $file ? $this->fileUrlGenerator
          ->generateAbsoluteString($file->getFileUri()) : '';
      }

      $created_date = $this->dateFormatter->format($record->created, 'solych_long_date');
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
