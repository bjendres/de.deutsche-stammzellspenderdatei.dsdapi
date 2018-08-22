<?php
/*------------------------------------------------------------+
| Deutsche Stammzellspenderdatei API extension                |
| Copyright (C) 2018 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

use CRM_Dsdapi_ExtensionUtil as E;

/**
 * Submit a contact update.
 *
 * @param array $params
 *   Associative array of property name/value pairs.
 *
 * @return array api result array
 *
 * @access public
 *
 * @throws \API_Exception
 */
function civicrm_api3_d_s_d_contact_update_submit($params) {
  // Log the API call to the CiviCRM debug log.
  if (defined('DSD_API_LOGGING') && DSD_API_LOGGING) {
    CRM_Core_Error::debug_log_message('DSDContactUpdate.submit: ' . json_encode($params));
  }

  try {
    // Update or create contact.
    $contact_data = array_intersect_key($params, array_flip(array(
      'external_identifier',
      'email',
      'prefix_id',
      'formal_title',
      'first_name',
      'last_name',
      'birth_date',
      'source',
      'created_date',
    )));

    // Derive gender from given prfix.
    if (!empty($params['prefix_id'])) {
      $prefix_id = CRM_Core_OptionGroup::getValue('individual_prefix', $params['prefix_id'], 'label');
      $contact_data['gender'] = CRM_Dsdapi_GenderPrefix::deriveGenderFromPrefix($prefix_id);
    }

    $contact = civicrm_api3('Contact', 'get', array(
      'external_identifier' => $params['external_identifier'],
    ));
    if ($contact['count'] > 1) {
      throw new CiviCRM_API3_Exception(
        E::ts('Found more than one contact with the given external identifier'),
        'api_error'
      );
    }
    elseif ($contact['count'] == 1) {
      $contact_data['id'] = reset($contact['values'])['id'];
    }
    else {
      $contact_data['contact_type'] = 'Individual';
    }
    $contact = civicrm_api3('Contact', 'create', $contact_data);
    if ($contact['is_error']) {
      throw new CiviCRM_API3_Exception(
        E::ts('Could not create or update the contact.'),
        'api_error'
      );
    }
    // Update e-mail address.
    if (!empty($params['email'])) {
      $email_data = array(
        'contact_id' => $contact['id'],
        'location_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
        'email' => $params['email'],
        'is_primary' => 1,
      );
      $email = civicrm_api3('Email', 'get', array(
        'contact_id' => $contact['id'],
        'lcoation_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
        'is_primary' => 1,
      ));
      if ($email['count'] > 1) {
        throw new CiviCRM_API3_Exception(
          E::ts('Found more than one e-mail address for the given contact.'),
          'api_error'
        );
      }
      if ($email['count'] == 1) {
        $email_data['id'] = reset($email['values'])['id'];
      }
      $email = civicrm_api3('Email', 'create', $email_data);
      if ($email['is_error']) {
        throw new CiviCRM_API3_Exception(
          E::ts('Could not create or update the contact\'s e-mail address.'),
          'api_error'
        );
      }
    }

    // Update or create address.
    $address_data = array_intersect_key($params, array_flip(array(
      'street_address',
      'postal_code',
      'city',
    )));
    if (!empty($address_data)) {
      $address_data += array(
        'contact_id' => $contact['id'],
        'location_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
      );
      $address = civicrm_api3('Address', 'get', array(
        'contact_id' => $contact['id'],
        'location_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
      ));
      if ($address['count'] > 1) {
        throw new CiviCRM_API3_Exception(
          E::ts('Found more than one address for the given contact.'),
          'api_error'
        );
      }
      if ($address['count'] == 1) {
        $address_data['id'] = reset($address['values'])['id'];
      }
      $address = civicrm_api3('Address', 'create', $address_data);
      if ($address['is_error']) {
        throw new CiviCRM_API3_Exception(
          E::ts('Could not create or update the contact\'s address.'),
          'api_error'
        );
      }
    }

    // Update or create phone numbers.
    if (!empty($params['phone_landline'])) {
      $phone_landline_data = array(
        'contact_id' => $contact['id'],
        'location_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
        'phone_type_id' => CRM_Dsdapi_Submission::PHONE_TYPE_ID_LANDLINE,
        'phone' => $params['phone_landline'],
      );
      $phone_landline = civicrm_api3('Phone', 'get', array(
        'contact_id' => $contact['id'],
        'lcoation_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
        'phone_type_id' => CRM_Dsdapi_Submission::PHONE_TYPE_ID_LANDLINE,
      ));
      if ($phone_landline['count'] > 1) {
        throw new CiviCRM_API3_Exception(
          E::ts('Found more than one landline phone number for the given contact.'),
          'api_error'
        );
      }
      if ($phone_landline['count'] == 1) {
        $phone_landline_data['id'] = reset($phone_landline['values'])['id'];
      }
      $phone_landline = civicrm_api3('Phone', 'create', $phone_landline_data);
      if ($phone_landline['is_error']) {
        throw new CiviCRM_API3_Exception(
          E::ts('Could not create or update the contact\'s landline phone.'),
          'api_error'
        );
      }
    }

    if (!empty($params['phone_mobile'])) {
      $phone_mobile_data = array(
        'contact_id' => $contact['id'],
        'location_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
        'phone_type_id' => CRM_Dsdapi_Submission::PHONE_TYPE_ID_MOBILE,
        'phone' => $params['phone_mobile'],
      );
      $phone_mobile = civicrm_api3('Phone', 'get', array(
        'contact_id' => $contact['id'],
        'lcoation_type_id' => CRM_Dsdapi_Submission::LOCATION_TYPE_ID_HOME,
        'phone_type_id' => CRM_Dsdapi_Submission::PHONE_TYPE_ID_MOBILE,
      ));
      if ($phone_mobile['count'] > 1) {
        throw new CiviCRM_API3_Exception(
          E::ts('Found more than one mobile phone number for the given contact.'),
          'api_error'
        );
      }
      if ($phone_mobile['count'] == 1) {
        $phone_mobile_data['id'] = reset($phone_mobile['values'])['id'];
      }
      $phone_mobile = civicrm_api3('Phone', 'create', $phone_mobile_data);
      if ($phone_mobile['is_error']) {
        throw new CiviCRM_API3_Exception(
          E::ts('Could not create or update the contact\'s mobile phone.'),
          'api_error'
        );
      }
    }

    // Update or create tags.
    if (!empty($params['tags'])) {
      // Allow single string values.
      if (!is_array($params['tags'])) {
        $params['tags'] = array($params['tags']);
      }

      // Translate tags into tag IDs by either finding existing tags or creating them.
      $tag_ids = array();
      foreach ($params['tags'] as $tag_name) {
        $tag = civicrm_api3('Tag', 'get', array(
          'name' => $tag_name
        ));
        if ($tag['count'] != 1) {
          // Create tag.
          $tag = civicrm_api3('Tag', 'create', array(
            'name' => $tag_name,
          ));
          $tag_ids[] = $tag['id'];
        }
        else {
          $tag_ids[] = reset($tag['values'])['id'];
        }
      }

      $current_tags = civicrm_api3('EntityTag', 'get', array(
        'entity_table' => 'civicrm_contact',
        'entity_id' => $contact['id'],
      ));
      $current_tags = array_map(function($tag) {
        return $tag['tag_id'];
      }, $current_tags['values']);

      $tags_to_remove = array_diff($current_tags, $tag_ids);
      foreach ($tags_to_remove as $tag) {
        $tagParams = array(
          'entity_table' => 'civicrm_contact',
          'entity_id' => $contact['id'],
          'tag_id' => (int) $tag,
        );
        // Doesn't seem to work with the API. This is used by CiviCRM core and
        // works.
        CRM_Core_BAO_EntityTag::del($tagParams);
      }

      $tags_to_add = array_diff($tag_ids, $current_tags);
      foreach ($tags_to_add as $tag) {
        civicrm_api3('EntityTag', 'create', array(
          'entity_table' => 'civicrm_contact',
          'entity_id' => $contact['id'],
          'tag_id' => (int) $tag,
        ));
      }
    }

    // TODO: Update or create notes.

    // TODO: Assemble return values.

    return civicrm_api3_create_success();
  }
  catch (Exception $exception) {
    if (defined('DSD_API_LOGGING') && DSD_API_LOGGING) {
      CRM_Core_Error::debug_log_message('DSDContactUpdate:submit:Exception caught: ' . $exception->getMessage());
    }

    $extraParams = (method_exists($exception, 'getExtraParams') ? $exception->getExtraParams() : array());

    return civicrm_api3_create_error($exception->getMessage(), $extraParams);
  }
}

/**
 * Parameter specification for the "Submit" action on "DSDContactUpdate"
 * entities.
 *
 * @param $params
 */
function _civicrm_api3_d_s_d_contact_update_submit_spec(&$params) {
  $params['external_identifier'] = array(
    'name' => 'external_identifier',
    'title' => 'External identifier',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => 'The external ID of the contact to create or update.',
  );
  $params['email'] = array(
    'name' => 'email',
    'title' => 'E-mail address',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The e-mail address of the contact to create or update.',
  );
  $params['prefix_id'] = array(
    'name' => 'prefix_id',
    'title' => 'Prefix',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'the ID of the prefix (salutation) of the contact to create or update.',
  );
  $params['formal_title'] = array(
    'name' => 'formal_title',
    'title' => 'Formal title',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The formal title of the contact to create or update.',
  );
  $params['first_name'] = array(
    'name' => 'first_name',
    'title' => 'First name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The first name of the contact to create or update.',
  );
  $params['last_name'] = array(
    'name' => 'last_name',
    'title' => 'Last name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The last name of the contact to create or update.',
  );
  $params['birth_date'] = array(
    'name' => 'birth_date',
    'title' => 'Birth date',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The birth date of the contact to create or update.',
  );
  $params['source'] = array(
    'name' => 'source',
    'title' => 'Source',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The source of the contact to create or update.',
  );
  $params['street_address'] = array(
    'name' => 'street_address',
    'title' => 'Street address',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The street address of the contact to create or update.',
  );
  $params['city'] = array(
    'name' => 'city',
    'title' => 'City',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The city of the contact to create or update.',
  );
  $params['postal_code'] = array(
    'name' => 'postal_code',
    'title' => 'Postal code',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The postal code of the contact to create or update.',
  );
  $params['phone_landline'] = array(
    'name' => 'phone_landline',
    'title' => 'Phone number (landline)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The landline phone number of the contact to create or update.',
  );
  $params['phone_mobile'] = array(
    'name' => 'phone_mobile',
    'title' => 'Phone number (mobile)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The mobile phone number of the contact to create or update.',
  );
  $params['tags'] = array(
    'name' => 'tags',
    'title' => 'Tags',
    'type' => CRM_Utils_Type::T_ENUM,
    'api.required' => 0,
    'description' => 'The tags of the contact to create or update.',
  );
  $params['note'] = array(
    'name' => 'note',
    'title' => 'Note',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The notes of the contact to create or update.',
  );
  $params['reg_date'] = array(
    'name' => 'reg_date',
    'title' => 'Registration date',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The registration date of the contact to create or update.',
  );
}
