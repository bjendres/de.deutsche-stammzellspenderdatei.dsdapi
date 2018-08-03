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

class CRM_Dsdapi_Submission {

  /**
   * Location type ID for location type "Home".
   */
  const LOCATION_TYPE_ID_HOME = 1;

  /**
   * Phone type ID for phone type "Phone".
   */
  const PHONE_TYPE_ID_LANDLINE = 'Phone';

  /**
   * Phone type ID for phone type "Mobile".
   */
  const PHONE_TYPE_ID_MOBILE = 'Mobile';

}
