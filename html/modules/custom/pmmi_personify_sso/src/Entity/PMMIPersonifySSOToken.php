<?php

namespace Drupal\pmmi_personify_sso\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Oauth2 Token entity.
 *
 * @ingroup pmmi_personify_sso
 *
 * @ContentEntityType(
 *   id = "personify_sso_token",
 *   label = @Translation("Personify SSO token"),
 *   handlers = {
 *     "list_builder" = "Drupal\pmmi_personify_sso\PMMIPersonifySSOTokenListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\pmmi_personify_sso\Entity\Form\PMMIPersonifySSOTokenDeleteForm",
 *     },
 *     "access" = "Drupal\pmmi_personify_sso\AccessTokenAccessControlHandler",
 *   },
 *   base_table = "personify_sso_token",
 *   admin_permission = "administer pmmi personify sso token",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "value",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/pmmi_personify_sso/{personify_sso_token}",
 *     "delete-form" = "/admin/content/pmmi_personify_sso/{personify_sso_token}/delete"
 *   }
 * )
 */
class PMMIPersonifySSOToken extends ContentEntityBase implements PMMIPersonifySSOTokenInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Access Token entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Access Token entity.'))
      ->setReadOnly(TRUE);

    $fields['auth_user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID of the user this access token is authenticating.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'author',
        'weight' => 1,
      ))
      ->setCardinality(1)
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ));

    $fields['scopes'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Scopes'))
      ->setDescription(t('The scopes for this Access Token. OAuth2 scopes are implemented as Drupal roles.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ));

    $fields['value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('The token value.'))
      ->setSettings(array(
        'max_length' => 128,
        'text_processing' => 0,
      ))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 4,
      ));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 5,
      ));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 6,
      ));

    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expire'))
      ->setDescription(t('The time when the token expires.'))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 7,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 7,
      ))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the token is available.'))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 8,
      ))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function revoke() {
    $this->set('status', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRevoked() {
    return !$this->get('status')->value;
  }

}
