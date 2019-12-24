<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Modules_Admin_Bar
 *
 * This class adds admin bar menu for Popup Management.
 */
class PUM_Modules_Admin_Bar {

	/**
	 * Initializes this module.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar_links' ), 999 );
		add_action( 'wp_footer', array( __CLASS__, 'admin_bar_styles' ), 999 );
		add_action( 'init', array( __CLASS__, 'show_debug_bar' ) );
	}

	/**
	 * Renders the admin debug bar when PUM Debug is enabled.
	 */
	public static function show_debug_bar() {
		if ( self::should_render() && Popup_Maker::debug_mode() ) {
			show_admin_bar( true );
		}
	}

	/**
	 * Returns true only if all of the following are true:
	 * - User is logged in.
	 * - Not in WP Admin.
	 * - The admin bar is showing.
	 * - PUM Admin bar is not disabled.
	 * - Current user can edit others posts or manage options.
	 *
	 * @return bool
	 */
	public static function should_render() {
		$tests = array(
			is_user_logged_in(),
			! is_admin(),
			is_admin_bar_showing(),
			! pum_get_option( 'disabled_admin_bar' ),
			( current_user_can( 'edit_others_posts' ) || current_user_can( 'manage_options' ) ),
		);

		return ! in_array( false, $tests );
	}

	/**
	 * Render admin bar scripts & styles if the toolbar button should show.
	 *
	 * TODO move this to external assets & use wp_enqueue_*
	 */
	public static function admin_bar_styles() {

		if ( ! self::should_render() ) {
			return;
		} ?>

        <style id="pum-admin-bar-styles">
            /* Layer admin bar over popups. */
            #wpadminbar {
                z-index: 999999999999;
            }

            #wp-admin-bar-popup-maker > .ab-item::before {
                background: url("<?php echo POPMAKE_URL; ?>/assets/images/admin/icon-info-21x21.png") center center no-repeat transparent !important;
                top: 3px;
                content: "";
                width: 20px;
                height: 20px;
            }

            #wp-admin-bar-popup-maker:hover > .ab-item::before {
                background-image: url("<?php echo POPMAKE_URL; ?>/assets/images/admin/icon-info-21x21.png") !important;
            }
        </style>
        <script id="pum-admin-bar-tools" type="text/javascript">
            /**
             * CssSelectorGenerator
             */
            (function () {
                var CssSelectorGenerator, root,
                    indexOf = [].indexOf || function (item) {
                        for (var i = 0, l = this.length; i < l; i++) {
                            if (i in this && this[i] === item) return i;
                        }
                        return -1;
                    };

                CssSelectorGenerator = (function () {
                    CssSelectorGenerator.prototype.default_options = {
                        selectors: ['id', 'class', 'tag', 'nthchild']
                    };

                    function CssSelectorGenerator(options) {
                        if (options == null) {
                            options = {};
                        }
                        this.options = {};
                        this.setOptions(this.default_options);
                        this.setOptions(options);
                    }

                    CssSelectorGenerator.prototype.setOptions = function (options) {
                        var key, results, val;
                        if (options == null) {
                            options = {};
                        }
                        results = [];
                        for (key in options) {
                            val = options[key];
                            if (this.default_options.hasOwnProperty(key)) {
                                results.push(this.options[key] = val);
                            } else {
                                results.push(void 0);
                            }
                        }
                        return results;
                    };

                    CssSelectorGenerator.prototype.isElement = function (element) {
                        return !!((element != null ? element.nodeType : void 0) === 1);
                    };

                    CssSelectorGenerator.prototype.getParents = function (element) {
                        var current_element, result;
                        result = [];
                        if (this.isElement(element)) {
                            current_element = element;
                            while (this.isElement(current_element)) {
                                result.push(current_element);
                                current_element = current_element.parentNode;
                            }
                        }
                        return result;
                    };

                    CssSelectorGenerator.prototype.getTagSelector = function (element) {
                        return this.sanitizeItem(element.tagName.toLowerCase());
                    };

                    CssSelectorGenerator.prototype.sanitizeItem = function (item) {
                        var characters;
                        characters = (item.split('')).map(function (character) {
                            if (character === ':') {
                                return "\\" + (':'.charCodeAt(0).toString(16).toUpperCase()) + " ";
                            } else if (/[ !"#$%&'()*+,.\/;<=>?@\[\\\]^`{|}~]/.test(character)) {
                                return "\\" + character;
                            } else {
                                return escape(character).replace(/\%/g, '\\');
                            }
                        });
                        return characters.join('');
                    };

                    CssSelectorGenerator.prototype.getIdSelector = function (element) {
                        var id, sanitized_id;
                        id = element.getAttribute('id');
                        if ((id != null) && (id !== '') && !(/\s/.exec(id)) && !(/^\d/.exec(id))) {
                            sanitized_id = "#" + (this.sanitizeItem(id));
                            if (element.ownerDocument.querySelectorAll(sanitized_id).length === 1) {
                                return sanitized_id;
                            }
                        }
                        return null;
                    };

                    CssSelectorGenerator.prototype.getClassSelectors = function (element) {
                        var class_string, item, result;
                        result = [];
                        class_string = element.getAttribute('class');
                        if (class_string != null) {
                            class_string = class_string.replace(/\s+/g, ' ');
                            class_string = class_string.replace(/^\s|\s$/g, '');
                            if (class_string !== '') {
                                result = (function () {
                                    var k, len, ref, results;
                                    ref = class_string.split(/\s+/);
                                    results = [];
                                    for (k = 0, len = ref.length; k < len; k++) {
                                        item = ref[k];
                                        results.push("." + (this.sanitizeItem(item)));
                                    }
                                    return results;
                                }).call(this);
                            }
                        }
                        return result;
                    };

                    CssSelectorGenerator.prototype.getAttributeSelectors = function (element) {
                        var attribute, blacklist, k, len, ref, ref1, result;
                        result = [];
                        blacklist = ['id', 'class'];
                        ref = element.attributes;
                        for (k = 0, len = ref.length; k < len; k++) {
                            attribute = ref[k];
                            if (ref1 = attribute.nodeName, indexOf.call(blacklist, ref1) < 0) {
                                result.push("[" + attribute.nodeName + "=" + attribute.nodeValue + "]");
                            }
                        }
                        return result;
                    };

                    CssSelectorGenerator.prototype.getNthChildSelector = function (element) {
                        var counter, k, len, parent_element, sibling, siblings;
                        parent_element = element.parentNode;
                        if (parent_element != null) {
                            counter = 0;
                            siblings = parent_element.childNodes;
                            for (k = 0, len = siblings.length; k < len; k++) {
                                sibling = siblings[k];
                                if (this.isElement(sibling)) {
                                    counter++;
                                    if (sibling === element) {
                                        return ":nth-child(" + counter + ")";
                                    }
                                }
                            }
                        }
                        return null;
                    };

                    CssSelectorGenerator.prototype.testSelector = function (element, selector) {
                        var is_unique, result;
                        is_unique = false;
                        if ((selector != null) && selector !== '') {
                            result = element.ownerDocument.querySelectorAll(selector);
                            if (result.length === 1 && result[0] === element) {
                                is_unique = true;
                            }
                        }
                        return is_unique;
                    };

                    CssSelectorGenerator.prototype.getAllSelectors = function (element) {
                        var result;
                        result = {
                            t: null,
                            i: null,
                            c: null,
                            a: null,
                            n: null
                        };
                        if (indexOf.call(this.options.selectors, 'tag') >= 0) {
                            result.t = this.getTagSelector(element);
                        }
                        if (indexOf.call(this.options.selectors, 'id') >= 0) {
                            result.i = this.getIdSelector(element);
                        }
                        if (indexOf.call(this.options.selectors, 'class') >= 0) {
                            result.c = this.getClassSelectors(element);
                        }
                        if (indexOf.call(this.options.selectors, 'attribute') >= 0) {
                            result.a = this.getAttributeSelectors(element);
                        }
                        if (indexOf.call(this.options.selectors, 'nthchild') >= 0) {
                            result.n = this.getNthChildSelector(element);
                        }
                        return result;
                    };

                    CssSelectorGenerator.prototype.testUniqueness = function (element, selector) {
                        var found_elements, parent;
                        parent = element.parentNode;
                        found_elements = parent.querySelectorAll(selector);
                        return found_elements.length === 1 && found_elements[0] === element;
                    };

                    CssSelectorGenerator.prototype.testCombinations = function (element, items, tag) {
                        var item, k, l, len, len1, ref, ref1;
                        ref = this.getCombinations(items);
                        for (k = 0, len = ref.length; k < len; k++) {
                            item = ref[k];
                            if (this.testUniqueness(element, item)) {
                                return item;
                            }
                        }
                        if (tag != null) {
                            ref1 = items.map(function (item) {
                                return tag + item;
                            });
                            for (l = 0, len1 = ref1.length; l < len1; l++) {
                                item = ref1[l];
                                if (this.testUniqueness(element, item)) {
                                    return item;
                                }
                            }
                        }
                        return null;
                    };

                    CssSelectorGenerator.prototype.getUniqueSelector = function (element) {
                        var found_selector, k, len, ref, selector_type, selectors;
                        selectors = this.getAllSelectors(element);
                        ref = this.options.selectors;
                        for (k = 0, len = ref.length; k < len; k++) {
                            selector_type = ref[k];
                            switch (selector_type) {
                            case 'id':
                                if (selectors.i != null) {
                                    return selectors.i;
                                }
                                break;
                            case 'tag':
                                if (selectors.t != null) {
                                    if (this.testUniqueness(element, selectors.t)) {
                                        return selectors.t;
                                    }
                                }
                                break;
                            case 'class':
                                if ((selectors.c != null) && selectors.c.length !== 0) {
                                    found_selector = this.testCombinations(element, selectors.c, selectors.t);
                                    if (found_selector) {
                                        return found_selector;
                                    }
                                }
                                break;
                            case 'attribute':
                                if ((selectors.a != null) && selectors.a.length !== 0) {
                                    found_selector = this.testCombinations(element, selectors.a, selectors.t);
                                    if (found_selector) {
                                        return found_selector;
                                    }
                                }
                                break;
                            case 'nthchild':
                                if (selectors.n != null) {
                                    return selectors.n;
                                }
                            }
                        }
                        return '*';
                    };

                    CssSelectorGenerator.prototype.getSelector = function (element) {
                        var all_selectors, item, k, l, len, len1, parents, result, selector, selectors;
                        all_selectors = [];
                        parents = this.getParents(element);
                        for (k = 0, len = parents.length; k < len; k++) {
                            item = parents[k];
                            selector = this.getUniqueSelector(item);
                            if (selector != null) {
                                all_selectors.push(selector);
                            }
                        }
                        selectors = [];
                        for (l = 0, len1 = all_selectors.length; l < len1; l++) {
                            item = all_selectors[l];
                            selectors.unshift(item);
                            result = selectors.join(' > ');
                            if (this.testSelector(element, result)) {
                                return result;
                            }
                        }
                        return null;
                    };

                    CssSelectorGenerator.prototype.getCombinations = function (items) {
                        var i, j, k, l, ref, ref1, result;
                        if (items == null) {
                            items = [];
                        }
                        result = [[]];
                        for (i = k = 0, ref = items.length - 1; 0 <= ref ? k <= ref : k >= ref; i = 0 <= ref ? ++k : --k) {
                            for (j = l = 0, ref1 = result.length - 1; 0 <= ref1 ? l <= ref1 : l >= ref1; j = 0 <= ref1 ? ++l : --l) {
                                result.push(result[j].concat(items[i]));
                            }
                        }
                        result.shift();
                        result = result.sort(function (a, b) {
                            return a.length - b.length;
                        });
                        result = result.map(function (item) {
                            return item.join('');
                        });
                        return result;
                    };

                    return CssSelectorGenerator;

                })();

                if (typeof define !== "undefined" && define !== null ? define.amd : void 0) {
                    define([], function () {
                        return CssSelectorGenerator;
                    });
                } else {
                    root = typeof exports !== "undefined" && exports !== null ? exports : this;
                    root.CssSelectorGenerator = CssSelectorGenerator;
                }

            }).call(this);

            (function ($) {
                var selector_generator = new CssSelectorGenerator;

                $(document).on('click', '#wp-admin-bar-pum-get-selector', function (event) {

                    alert("<?php _e( 'After clicking ok, click the element you want a selector for.', 'popup-maker' ); ?>");

                    event.preventDefault();
                    event.stopPropagation();

                    $(document).one('click', function (event) {
                        // get reference to the element user clicked on
                        var element = event.target,
                            // get unique CSS selector for that element
                            selector = selector_generator.getSelector(element);

                        alert("<?php _ex( 'Selector', 'JS alert for CSS get selector tool', 'popup-maker' ); ?>: " + selector);

                        event.preventDefault();
                        event.stopPropagation();
                    });
                });
            }(jQuery));
        </script><?php
	}

	/**
	 * Add additional toolbar menu items to the front end.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public static function toolbar_links( $wp_admin_bar ) {

		if ( ! self::should_render() ) {
			return;
		}

		$wp_admin_bar->add_node( array(
			'id'     => 'popup-maker',
			'title'  => __( 'Popup Maker', 'popup-maker' ),
			'href'   => '#popup-maker',
			'meta'   => array( 'class' => 'popup-maker-toolbar' ),
			'parent' => false,
		) );

		$popups_url = current_user_can( 'edit_posts' ) ? admin_url( 'edit.php?post_type=popup' ) : '#';

		$wp_admin_bar->add_node( array(
			'id'     => 'popups',
			'title'  => __( 'Popups', 'popup-maker' ),
			'href'   => $popups_url,
			'parent' => 'popup-maker',
		) );

		$popups = PUM_Modules_Admin_Bar::loaded_popups();

		if ( count( $popups ) ) {

			foreach ( $popups as $popup ) {
				/** @var WP_Post $popup */

				$node_id = 'popup-' . $popup->ID;

				$can_edit = current_user_can( 'edit_post', $popup->ID );

				$edit_url = $can_edit ? admin_url( 'post.php?post=' . $popup->ID . '&action=edit' ) : '#';

				// Single Popup Menu Node
				$wp_admin_bar->add_node( array(
					'id'     => $node_id,
					'title'  => $popup->post_title,
					'href'   => $edit_url,
					'parent' => 'popups',
				) );

				// Trigger Link
				$wp_admin_bar->add_node( array(
					'id'     => $node_id . '-open',
					'title'  => __( 'Open Popup', 'popup-maker' ),
					'meta'   => array(
						'onclick' => 'PUM.open(' . $popup->ID . '); return false;',
					),
					'href'   => '#popup-maker-open-popup-' . $popup->ID,
					'parent' => $node_id,
				) );

				$wp_admin_bar->add_node( array(
					'id'     => $node_id . '-close',
					'title'  => __( 'Close Popup', 'popup-maker' ),
					'meta'   => array(
						'onclick' => 'PUM.close(' . $popup->ID . '); return false;',
					),
					'href'   => '#popup-maker-close-popup-' . $popup->ID,
					'parent' => $node_id,
				) );

				if ( pum_get_popup( $popup->ID )->has_conditions( array( 'js_only' => true ) ) ) {
					$wp_admin_bar->add_node( array(
						'id'     => $node_id . '-conditions',
						'title'  => __( 'Check Conditions', 'popup-maker' ),
						'meta'   => array(
							'onclick' => 'alert(PUM.checkConditions(' . $popup->ID . ') ? "Pass" : "Fail"); return false;',
						),
						'href'   => '#popup-maker-check-conditions-popup-' . $popup->ID,
						'parent' => $node_id,
					) );
				}

				$wp_admin_bar->add_node( array(
					'id'     => $node_id . '-reset-cookies',
					'title'  => __( 'Reset Cookies', 'popup-maker' ),
					'meta'   => array(
						'onclick' => 'PUM.clearCookies(' . $popup->ID . '); alert("' . __( 'Success', 'popup-maker' ) . '"); return false;',
					),
					'href'   => '#popup-maker-reset-cookies-popup-' . $popup->ID,
					'parent' => $node_id,
				) );

				if ( $can_edit ) {
					// Edit Popup Link
					$wp_admin_bar->add_node( array(
						'id'     => $node_id . '-edit',
						'title'  => __( 'Edit Popup', 'popup-maker' ),
						'href'   => $edit_url,
						'parent' => $node_id,
					) );
				}

			}
		} else {
			$wp_admin_bar->add_node( array(
				'id'     => 'no-popups-loaded',
				'title'  => __( 'No Popups Loaded', 'popup-maker' ) . '<strong style="color:#fff; margin-left: 5px;">?</strong>',
				'href'   => 'https://docs.wppopupmaker.com/article/265-my-popup-wont-work-how-can-i-fix-it?utm_capmaign=Self+Help&utm_source=No+Popups&utm_medium=Admin+Bar',
				'parent' => 'popups',
				'meta'   => array(
					'target' => '_blank',
				),

			) );
		}

		/**
		 * Tools
		 */
		$wp_admin_bar->add_node( array(
			'id'     => 'pum-tools',
			'title'  => __( 'Tools', 'popup-maker' ),
			'href'   => '#popup-maker-tools',
			'parent' => 'popup-maker',
		) );

		/**
		 * Get Selector
		 */
		$wp_admin_bar->add_node( array(
			'id'     => 'pum-get-selector',
			'title'  => __( 'Get Selector', 'popup-maker' ),
			'href'   => '#popup-maker-get-selector-tool',
			'parent' => 'pum-tools',
		) );

	}

	/**
	 * @return array
	 */
	public static function loaded_popups() {
		static $popups;

		if ( ! isset( $popups ) ) {
			$loaded = PUM_Site_Popups::get_loaded_popups();
			$popups = $loaded->posts;
		}

		return $popups;
	}
}

PUM_Modules_Admin_Bar::init();