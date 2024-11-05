<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Markup;
use Drupal\solych\Form\CatsEditForm;
use Drupal\solych\Form\CatsForm;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Cats Add page controller.
 *
 * This controller generates the Cats page, which includes a title,
 * descriptive text, and a form for submitting cat and owner information.
 */
class CatsController extends ControllerBase {


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The cache invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheInvalidator;

  /**
   * Constructs the CatsForm.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_invalidator
   *    The cache tags invalidator service.
   */
  public function __construct(Connection $database, CacheTagsInvalidator $cache_invalidator)
  {
    $this->database = $database;
    $this->cacheInvalidator = $cache_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * Returns the content for the Cats add page.
   *
   * @return array
   *   A render array containing the title, markup, and form.
   */
  public function content()
  {
    $form = $this->formBuilder()->getForm(CatsForm::class);

    $list_button = [
      '#type' => 'link',
      '#title' => $this->t('View Cats List'),
      '#url' => Url::fromRoute('solych.cats_list'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
    ];

    return [
      '#theme' => 'cats-page',
      '#title' => $this->t('Cats page'),
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
      '#cats_form' => $form,
      '#list_button' => $list_button,
    ];
  }


  /**
   * Provides a delete confirmation modal dialog.
   *
   * @param int $id
   *   The ID of the cat record to delete.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response with modal dialog commands.
   */
  public function delete($id)
  {
    $response = new AjaxResponse();

    $modal_content = [
      '#type' => 'markup',
      '#markup' => Markup::create('
      <div id="delete-confirmation">
        <p>' . $this->t('Are you sure you want to delete this cat?') . '</p>
        <div class="delete-confirm-dialog-buttons">
          <button type="button" class="button button--danger delete-confirm-yes">' . $this->t('Delete') . '</button>
          <button type="button" class="button delete-confirm-no">' . $this->t('Cancel') . '</button>
        </div>
      </div>'),
      '#attached' => [
        'drupalSettings' => [
          'solych' => [
            'deleteConfirmUrl' => Url::fromRoute('solych.cat_delete_confirm', ['id' => $id])->toString(),
            'deleteId' => $id,
          ],
        ],
      ],
    ];

    $response->addCommand(new OpenModalDialogCommand(
      $this->t('Delete Confirmation'),
      $modal_content,
      [
        'width' => '300',
        'dialogClass' => 'delete-confirm-dialog',
      ]
    ));

    return $response;
  }

  /**
   * Confirms the deletion of a cat record.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request with ID of the cat to delete.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response to redirect or update after deletion.
   */
  public function deleteConfirm(Request $request)
  {
    parse_str($request->getContent(), $data);

    $id = isset($data['id']) ? $data['id'] : NULL;
    if ($id) {
      $this->database->delete('solych')
        ->condition('id', $id)
        ->execute();

      $this->messenger()->addMessage($this->t('Cats deleted successfully.'));
    }
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $this->cacheInvalidator->invalidateTags(['solych_cat_list']);

    return $response;
  }

  /**
   * Provides an edit confirmation modal dialog.
   *
   * @param int $id
   *   The ID of the cat record to edit.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A response with modal dialog commands.
   */
  public function edit($id, Request $request) {
    $response = new AjaxResponse();
    $referer = $request->headers->get('referer');
    $form = $this->formBuilder()->getForm(CatsEditForm::class, $id, $referer);

    $response->addCommand(new OpenModalDialogCommand(
      $this->t('Edit'),
      $form,
      [
        'width' => '500',
        'dialogClass' => 'edit-cat-modal',
        'modal' => TRUE,
      ]
    ));
    $this->cacheInvalidator->invalidateTags(['solych_cat_list']);

    return $response;
  }
}
