<?php

namespace Drupal\ad_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Advertising entity.
 *
 * @ConfigEntityType(
 *   id = "ad_entity",
 *   label = @Translation("Advertising entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\ad_entity\AdEntityListBuilder",
 *     "view_builder" = "Drupal\ad_entity\AdEntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\ad_entity\Form\AdEntityForm",
 *       "edit" = "Drupal\ad_entity\Form\AdEntityForm",
 *       "delete" = "Drupal\ad_entity\Form\AdEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ad_entity\AdEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ad_entity",
 *   admin_permission = "administer advertising entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ad_entity/{ad_entity}",
 *     "add-form" = "/admin/structure/ad_entity/add",
 *     "edit-form" = "/admin/structure/ad_entity/{ad_entity}/edit",
 *     "delete-form" = "/admin/structure/ad_entity/{ad_entity}/delete",
 *     "collection" = "/admin/structure/ad_entity"
 *   }
 * )
 */
class AdEntity extends ConfigEntityBase implements AdEntityInterface {

  /**
   * The Advertising entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Advertising entity label.
   *
   * @var string
   */
  protected $label;

}