<?php

namespace Drupal\ad_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ad_entity\Plugin\AdContextManager;

/**
 * Plugin implementation of the 'ad_entity_context' field widget.
 *
 * @FieldWidget(
 *   id = "ad_entity_context",
 *   label = @Translation("Advertising context"),
 *   field_types = {
 *     "ad_entity_context"
 *   }
 * )
 */
class ContextWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Advertising context manager.
   *
   * @var \Drupal\ad_entity\Plugin\AdContextManager
   */
  protected $contextManager;

  /**
   * The storage for Advertising entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $adEntityStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('ad_entity.context_manager'),
      $container->get('entity_type.manager')->getStorage('ad_entity')
    );
  }

  /**
   * Constructs a AdContextWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\ad_entity\Plugin\AdContextManager $context_manager
   *   The Advertising context manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $ad_storage
   *   The storage for Advertising entities.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AdContextManager $context_manager, EntityStorageInterface $ad_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->contextManager = $context_manager;
    $this->adEntityStorage = $ad_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'context_plugin_id' => NULL,
      'apply_on' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $context_item = $items->get($delta)->get('context');

    $context_definitions = $this->contextManager->getDefinitions();
    $options = [];
    foreach ($context_definitions as $id => $definition) {
      $options[$id] = $definition['label'];
    }
    $selector = Crypt::randomBytesBase64(2);
    $element['context']['context_plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Context type'),
      '#required' => FALSE,
      '#options' => $options,
      '#empty_value' => '',
      '#attributes' => ['data-context-selector' => $selector],
      '#default_value' => $context_item->get('context_plugin_id')->getValue(),
      '#weight' => 10,
    ];

    $element['context']['context_settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ad-entity-context-settings']],
      '#states' => [
        'invisible' => [
          'select[data-context-selector="' . $selector . '"]' => ['value' => ''],
        ],
      ],
      '#weight' => 20,
    ];

    /** @var \Drupal\ad_entity\Entity\AdEntityInterface[] $entities */
    $entities = $this->adEntityStorage->loadMultiple();
    $options = [];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }
    $element['context']['apply_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Apply on ads'),
      '#description' => $this->t('Choose none to apply this context on any ad which would appear.'),
      '#required' => FALSE,
      '#multiple' => TRUE,
      '#options' => $options,
      '#empty_value' => '',
      '#default_value' => $context_item->get('apply_on')->getValue(),
      '#weight' => 30,
      '#states' => [
        'invisible' => [
          'select[data-context-selector="' . $selector . '"]' => ['value' => ''],
        ],
      ],
    ];

    // Build the settings form elements for the context plugins.
    $context_settings = [];
    foreach ($context_definitions as $id => $definition) {
      $context_plugin = $this->contextManager->createInstance($id);
      $item_settings = $context_item->get('context_settings')->getValue();
      $plugin_settings = !empty($item_settings[$id]) ? $item_settings[$id] : [];
      $context_settings[$id] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['ad-entity-context-' . $id]],
        '#states' => [
          'visible' => [
            'select[data-context-selector="' . $selector . '"]' => ['value' => $id],
          ],
        ],
      ];
      $context_settings[$id] += $context_plugin->settingsForm($plugin_settings, $context_item, $form, $form_state);
    }
    $element['context']['context_settings'] += $context_settings;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $index => &$value) {
      if (empty($value['context']['context_plugin_id'])) {
        // Remove the whole field value in case no context was chosen.
        unset($values[$index]);
      }
      else {
        // Let the context plugin massage its settings for storage and output.
        $id = $value['context']['context_plugin_id'];
        if ($this->contextManager->hasDefinition($id)) {
          $context_plugin = $this->contextManager->createInstance($id);
          $plugin_settings = !empty($value['context']['context_settings'][$id]) ?
            $value['context']['context_settings'][$id] : [];
          $plugin_settings = $context_plugin->massageSettings($plugin_settings);
          $value['context']['context_settings'] = [$id => $plugin_settings];
        }
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
