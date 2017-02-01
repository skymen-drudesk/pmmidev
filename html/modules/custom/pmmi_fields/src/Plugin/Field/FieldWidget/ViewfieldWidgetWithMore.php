<?php

/**
 * @file
 * Contains \Drupal\pmmi_fields\Plugin\Field\FieldWidget\ViewfieldWidgetWithMore.
 */

namespace Drupal\pmmi_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Drupal\views\Views;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\viewfield\Plugin\Field\FieldWidget\ViewfieldWidget;

/**
 * Plugin implementation of the 'viewfield' widget.
 *
 * @FieldWidget(
 *   id = "viewfield_select_with_more",
 *   label = @Translation("Select List with more link"),
 *   field_types = {
 *     "viewfield"
 *   }
 * )
 */
class ViewfieldWidgetWithMore extends ViewfieldWidget {

  protected $items;

  protected $delta;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->items = $items;
    $this->delta = $delta;

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_name = $this->fieldDefinition->getName();
    $id_prefix = implode('-', array_merge($element['#field_parents'], [$field_name]));
    $wrapper_id = Crypt::hashBase64($id_prefix . '-ajax-wrapper');

    $element += [
      '#type' => 'container',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $element['vname']['#ajax'] = [
      'callback' => [get_class($this), 'ajaxRefresh'],
      'wrapper' => $wrapper_id,
    ];

    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#name'] == $field_name . '[' . $delta . '][vname]') {
      $view_name = $triggering_element['#value'];
    }
    elseif (isset($items[$delta]->vname)) {
      $view_name = $items[$delta]->vname;
    }
    if (isset($view_name)) {
      $view = explode('|', $view_name);
      $view_instance = $this->getView($view[0], $view[1])->preview();
      $element['more'] = array(
        '#type' => 'container',
      );
      $this->prepareFormElements($element, $view_instance['#view']->display_handler, $view[1]);

      foreach (['attachment_after', 'attachment_before'] as $attachment_position) {
        if (!empty($view_instance['#view']->{$attachment_position})) {
          $attachment = $view_instance['#view']->{$attachment_position}[0]['#view'];
          $this->prepareFormElements($element, $attachment->display_handler, $attachment->current_display, $attachment_position);
        }

      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewOptions($handler, $option, $display) {
    $settings = array();
    if ($values = $this->items[$this->delta]->settings) {
      $settings = unserialize($values);
    }
    return isset($settings['more']) && isset($settings['more'][$display]) ? $settings['more'][$display][$option] : $handler->options[$option];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      $settings_array = array();
      foreach (['more', 'attachment_after_more', 'attachment_before_more'] as $more) {
        $values[$key]['settings'] = isset($values[$key]['settings']) ? $values[$key]['settings'] : '';
        if (!empty($value[$more])) {
          $settings_array += array($more => $value[$more]);
        }
      }
      $values[$key]['settings'] .= serialize($settings_array);
    }
    return $values;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($element['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Prepare form elements.
   */
  protected function prepareFormElements(&$element, $handler, $display, $attachment_position = '') {
    $input_name = $element['#field_name'] . '[' . $element['#delta'] . '][more][' . $display . '][use_more]';
    $display_name = !empty($attachment_position) ? $attachment_position : $display;
    $element['more'][$display] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('More link options for @display display', ['@display' => $display_name]),
    );
    $element['more'][$display]['use_more'] = array(
      '#type' => 'checkbox',
      '#title' => t('More link'),
      '#default_value' => $this->getViewOptions($handler, 'use_more', $display),
    );
    $element['more'][$display]['use_more_text'] = array(
      '#type' => 'textfield',
      '#title' => t('More link text'),
      '#default_value' => $this->getViewOptions($handler, 'use_more_text', $display),
      '#states' => array(
        'visible' => array(
          'input[name="' . $input_name . '"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['more'][$display]['link_url'] = array(
      '#type' => 'textfield',
      '#title' => t('More link url'),
      '#default_value' => $this->getViewOptions($handler, 'link_url', $display),
      '#states' => array(
        'visible' => array(
          'input[name="' . $input_name . '"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewSettings($view, $display, $settings) {}

}
