<?php

namespace Drupal\solych\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page with a table of all cats.
 */
class CatsListController extends ControllerBase {


  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new CatsListController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Displays the table with a button link to add cat form.
   *
   * @return array
   *   A render array containing the table and a back button.
   */
  public function content() {
    $table_block = $this->blockManager->createInstance('cats_table_block');
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
