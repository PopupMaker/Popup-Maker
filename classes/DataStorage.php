<?php

/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initializes a temporary data storage engine used by core in various capacities.
 *
 * @since 1.7.0
 *
 * @deprecated 1.8.0 Use PUM_Utils_DataStorage instead.
 */
class PUM_DataStorage extends PUM_Utils_DataStorage {}
