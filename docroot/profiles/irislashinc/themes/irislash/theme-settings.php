<?php

/**
 * @file
 * Theme settings form for irislash theme.
 */

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function irislash_form_system_theme_settings_alter(&$form, &$form_state) {

  $form['irislash'] = [
    '#type' => 'details',
    '#title' => t('irislash'),
    '#open' => TRUE,
  ];

  $form['irislash']['font_size'] = [
    '#type' => 'number',
    '#title' => t('Font size'),
    '#min' => 12,
    '#max' => 18,
    '#default_value' => theme_get_setting('font_size'),
  ];

}
