/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/block-editor/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayLikeToArray.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return arrayLikeToArray(arr);
}

module.exports = _arrayWithoutHoles;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/assertThisInitialized.js ***!
  \**********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/extends.js":
/*!********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/extends.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _extends() {
  module.exports = _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  return _extends.apply(this, arguments);
}

module.exports = _extends;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/getPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/inherits.js":
/*!*********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/inherits.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(/*! ./setPrototypeOf */ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArray.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArray.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}

module.exports = _iterableToArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableSpread.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableSpread.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableSpread;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js":
/*!************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js ***!
  \************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var objectWithoutPropertiesLoose = __webpack_require__(/*! ./objectWithoutPropertiesLoose */ "./node_modules/@babel/runtime/helpers/objectWithoutPropertiesLoose.js");

function _objectWithoutProperties(source, excluded) {
  if (source == null) return {};
  var target = objectWithoutPropertiesLoose(source, excluded);
  var key, i;

  if (Object.getOwnPropertySymbols) {
    var sourceSymbolKeys = Object.getOwnPropertySymbols(source);

    for (i = 0; i < sourceSymbolKeys.length; i++) {
      key = sourceSymbolKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
      target[key] = source[key];
    }
  }

  return target;
}

module.exports = _objectWithoutProperties;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/objectWithoutPropertiesLoose.js":
/*!*****************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/objectWithoutPropertiesLoose.js ***!
  \*****************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;

  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }

  return target;
}

module.exports = _objectWithoutPropertiesLoose;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/typeof.js");

var assertThisInitialized = __webpack_require__(/*! ./assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/setPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/toConsumableArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/toConsumableArray.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithoutHoles = __webpack_require__(/*! ./arrayWithoutHoles */ "./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js");

var iterableToArray = __webpack_require__(/*! ./iterableToArray */ "./node_modules/@babel/runtime/helpers/iterableToArray.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableSpread = __webpack_require__(/*! ./nonIterableSpread */ "./node_modules/@babel/runtime/helpers/nonIterableSpread.js");

function _toConsumableArray(arr) {
  return arrayWithoutHoles(arr) || iterableToArray(arr) || unsupportedIterableToArray(arr) || nonIterableSpread();
}

module.exports = _toConsumableArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

module.exports = _typeof;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;

/***/ }),

/***/ "./node_modules/classnames/index.js":
/*!******************************************!*\
  !*** ./node_modules/classnames/index.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
  Copyright (c) 2017 Jed Watson.
  Licensed under the MIT License (MIT), see
  http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;

	function classNames () {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg) && arg.length) {
				var inner = classNames.apply(null, arg);
				if (inner) {
					classes.push(inner);
				}
			} else if (argType === 'object') {
				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "./src/block-editor/block-extensions/index.js":
/*!****************************************************!*\
  !*** ./src/block-editor/block-extensions/index.js ***!
  \****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _popup_trigger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./popup-trigger */ "./src/block-editor/block-extensions/popup-trigger/index.js");
/**
 * Internal dependencies
 */


/***/ }),

/***/ "./src/block-editor/block-extensions/popup-trigger/index.js":
/*!******************************************************************!*\
  !*** ./src/block-editor/block-extensions/popup-trigger/index.js ***!
  \******************************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _components_popup_select_control__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../components/popup-select-control */ "./src/block-editor/components/popup-select-control/index.js");
/* harmony import */ var _icons_gears__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../icons/gears */ "./src/block-editor/icons/gears.js");


/**
 * External Dependencies
 */

/**
 * WordPress Dependencies
 */






/**
 * Internal dependencies
 */



/**
 * Either allowedBlocks or excludedBlocks should be used, not both.
 *
 * @type {Array}
 */

var allowedBlocks = [];
var excludedBlocks = pum_block_editor_vars.popup_trigger_excluded_blocks || ['core/nextpage'];

function isAllowedForBlockType(name) {
  if (!allowedBlocks.length && !excludedBlocks.length) {
    return true;
  }

  if (allowedBlocks.length) {
    return allowedBlocks.includes(name);
  }

  if (excludedBlocks.length) {
    return !excludedBlocks.includes(name);
  }

  return true;
}
/**
 * Add custom attribute for mobile visibility.
 *
 * @param {Object} settings Settings for the block.
 *
 * @return {Object} settings Modified settings.
 */


function addAttributes(settings) {
  //check if object exists for old Gutenberg version compatibility
  //add allowedBlocks restriction
  if (typeof settings.attributes !== 'undefined' && isAllowedForBlockType(settings.name)) {
    settings.attributes = Object.assign(settings.attributes, {
      openPopupId: {
        type: 'string',
        default: ''
      }
    });
  }

  return settings;
}
/**
 * Add mobile visibility controls on Advanced Block Panel.
 *
 * @param {Function} BlockEdit Block edit component.
 *
 * @return {Function} BlockEdit Modified block edit component.
 */


var withAdvancedControls = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_6__["createHigherOrderComponent"])(function (BlockEdit) {
  return function (props) {
    var name = props.name,
        attributes = props.attributes,
        setAttributes = props.setAttributes,
        isSelected = props.isSelected;
    var openPopupId = attributes.openPopupId;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(BlockEdit, props), isSelected && isAllowedForBlockType(name) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_4__["InspectorControls"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Panel"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["PanelBody"], {
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Popup Controls', 'popup-maker'),
      icon: _icons_gears__WEBPACK_IMPORTED_MODULE_8__["default"],
      initialOpen: false
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["PanelRow"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('These settings allow you to control popups with this block.', 'popup-maker')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["PanelRow"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_components_popup_select_control__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Open Popup', 'popup-maker'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Tooltip"], {
        position: "top",
        text: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('This method does not work well with all block types.', 'popup-maker')
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("a", {
        href: "https://docs.wppopupmaker.com/article/395-trigger-click-open-overview-methods",
        target: "_blank",
        rel: "noopener noreferrer"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Icon"], {
        size: "16",
        icon: "editor-help",
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Open documentation', 'popup-maker'),
        style: {
          verticalAlign: 'middle'
        }
      })))),
      value: openPopupId,
      onChange: function onChange(popupId) {
        return setAttributes({
          openPopupId: popupId
        });
      },
      help: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Open a popup when clicking this block', 'popup-maker')
    }))))));
  };
}, 'withAdvancedControls');
/**
 * Add custom element class in save element.
 *
 * @param {Object} extraProps     Block element.
 * @param {Object} blockType      Blocks object.
 * @param {Object} attributes     Blocks attributes.
 *
 * @return {Object} extraProps Modified block element.
 */

function applyTriggerClass(extraProps, blockType, attributes) {
  var openPopupId = attributes.openPopupId; //check if attribute exists for old Gutenberg version compatibility
  //add class only when visibleOnMobile = false
  //add allowedBlocks restriction

  if (typeof openPopupId !== 'undefined' && openPopupId > 0 && isAllowedForBlockType(blockType.name)) {
    extraProps.className = classnames__WEBPACK_IMPORTED_MODULE_1___default()(extraProps.className, 'popmake-' + openPopupId);
  }

  return extraProps;
} //add filters


Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__["addFilter"])('blocks.registerBlockType', 'popup-maker/popup-trigger-attributes', addAttributes);
Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__["addFilter"])('editor.BlockEdit', 'popup-maker/popup-trigger-advanced-control', withAdvancedControls);
Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_3__["addFilter"])('blocks.getSaveContent.extraProps', 'popup-maker/applyTriggerClass', applyTriggerClass);

/***/ }),

/***/ "./src/block-editor/components/popup-select-control/index.js":
/*!*******************************************************************!*\
  !*** ./src/block-editor/components/popup-select-control/index.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PopupSelectControl; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);








var _excluded = ["onChangeInputValue", "value", "label", "emptyValueLabel", "hideLabelFromVision"];


function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

//import Select from 'react-select/src/Select';

/**
 * WordPress dependencies
 */



/**
 * Internal vars.
 */

var popups = window.pum_block_editor_vars.popups;

var PopupSelectControl = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(PopupSelectControl, _Component);

  var _super = _createSuper(PopupSelectControl);

  function PopupSelectControl() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, PopupSelectControl);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(PopupSelectControl, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          onChangeInputValue = _this$props.onChangeInputValue,
          value = _this$props.value,
          _this$props$label = _this$props.label,
          label = _this$props$label === void 0 ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Select Popup', 'popup-maker') : _this$props$label,
          _this$props$emptyValu = _this$props.emptyValueLabel,
          emptyValueLabel = _this$props$emptyValu === void 0 ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Choose a popup', 'popup-maker') : _this$props$emptyValu,
          _this$props$hideLabel = _this$props.hideLabelFromVision,
          hideLabelFromVision = _this$props$hideLabel === void 0 ? false : _this$props$hideLabel,
          props = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2___default()(_this$props, _excluded);

      var options = [{
        value: '',
        label: emptyValueLabel
      }].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_1___default()(popups.map(function (popup) {
        return {
          value: "".concat(popup.ID),
          label: popup.post_title //disabled: true

        };
      })));
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "block-editor-popup-select-input"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["SelectControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
        label: label,
        hideLabelFromVision: hideLabelFromVision,
        value: value,
        onChange: onChangeInputValue,
        options: options
      }, props)));
    }
  }]);

  return PopupSelectControl;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);



/***/ }),

/***/ "./src/block-editor/components/trigger-popover/index.js":
/*!**************************************************************!*\
  !*** ./src/block-editor/components/trigger-popover/index.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return TriggerPopover; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__);








var _excluded = ["additionalControls", "children", "renderSettings", "position", "focusOnMount", "noticeUI"];


function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */



/**
 * Style Dependencies.
 * import './editor.scss';
 */

var TriggerPopover = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(TriggerPopover, _Component);

  var _super = _createSuper(TriggerPopover);

  function TriggerPopover() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, TriggerPopover);

    _this = _super.apply(this, arguments);
    _this.toggleSettingsVisibility = _this.toggleSettingsVisibility.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.state = {
      isSettingsExpanded: false
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(TriggerPopover, [{
    key: "toggleSettingsVisibility",
    value: function toggleSettingsVisibility() {
      this.setState({
        isSettingsExpanded: !this.state.isSettingsExpanded
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          additionalControls = _this$props.additionalControls,
          children = _this$props.children,
          renderSettings = _this$props.renderSettings,
          _this$props$position = _this$props.position,
          position = _this$props$position === void 0 ? 'bottom center' : _this$props$position,
          _this$props$focusOnMo = _this$props.focusOnMount,
          focusOnMount = _this$props$focusOnMo === void 0 ? 'firstElement' : _this$props$focusOnMo,
          noticeUI = _this$props.noticeUI,
          popoverProps = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(_this$props, _excluded);

      var isSettingsExpanded = this.state.isSettingsExpanded;
      var showSettings = !!renderSettings && isSettingsExpanded;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Popover"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
        className: "editor-popup-trigger-popover block-editor-popup-trigger-popover",
        focusOnMount: focusOnMount,
        position: position
      }, popoverProps), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "block-editor-popup-trigger-popover__input-container"
      }, noticeUI, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "editor-popup-trigger-popover__row block-editor-popup-trigger-popover__row"
      }, children, !!renderSettings && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["IconButton"], {
        className: "editor-popup-trigger-popover__settings-toggle block-editor-popup-trigger-popover__settings-toggle",
        icon: "arrow-down-alt2",
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Trigger settings', 'popup-maker'),
        onClick: this.toggleSettingsVisibility,
        "aria-expanded": isSettingsExpanded
      })), showSettings && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "editor-popup-trigger-popover__row block-editor-popup-trigger-popover__row editor-popup-trigger-popover__settings block-editor-popup-trigger-popover__settings"
      }, renderSettings())), additionalControls && !showSettings && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "block-editor-popup-trigger-popover__additional-controls"
      }, additionalControls));
    }
  }]);

  return TriggerPopover;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);



/***/ }),

/***/ "./src/block-editor/components/trigger-popover/popup-trigger-editor.js":
/*!*****************************************************************************!*\
  !*** ./src/block-editor/components/trigger-popover/popup-trigger-editor.js ***!
  \*****************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PopupTriggerEditor; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _popup_select_control__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../popup-select-control */ "./src/block-editor/components/popup-select-control/index.js");


var _excluded = ["className", "onChangeInputValue", "value"];


/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


function PopupTriggerEditor(_ref) {
  var className = _ref.className,
      onChangeInputValue = _ref.onChangeInputValue,
      value = _ref.value,
      props = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(_ref, _excluded);

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("form", _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    className: classnames__WEBPACK_IMPORTED_MODULE_3___default()('block-editor-popup-trigger-popover__popup-editor', className)
  }, props), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_popup_select_control__WEBPACK_IMPORTED_MODULE_6__["default"], {
    emptyValueLabel: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Which popup should open?', 'popup-maker'),
    hideLabelFromVision: true,
    value: value,
    onChange: onChangeInputValue,
    required: true // postType="popup"

  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["IconButton"], {
    icon: "editor-break",
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Apply', 'popup-maker'),
    type: "submit"
  }));
}

/***/ }),

/***/ "./src/block-editor/components/trigger-popover/popup-trigger-viewer.js":
/*!*****************************************************************************!*\
  !*** ./src/block-editor/components/trigger-popover/popup-trigger-viewer.js ***!
  \*****************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PopupTriggerViewer; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);


var _excluded = ["className", "spanClassName", "onEditLinkClick", "popupId"];


/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




var _ref = window.pum_block_editor_vars || [],
    popups = _ref.popups;

function getPopupById() {
  var popupId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
  popupId = parseInt(popupId) || 0;
  var popup = popups.filter(function (_ref2) {
    var ID = _ref2.ID;
    return popupId === ID;
  });
  return popup.length === 1 ? popup[0] : false;
}

function PopupView(_ref3) {
  var popupId = _ref3.popupId,
      className = _ref3.className;
  var spanClassName = classnames__WEBPACK_IMPORTED_MODULE_3___default()(className, 'block-editor-popup-trigger-popover__popup-viewer-text');
  var popup = getPopupById(popupId);
  var label = !!popup ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Open "%s" popup', 'popup-maker'), popup.post_title) : '';
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("span", {
    className: spanClassName
  }, label);
}

function PopupTriggerViewer(_ref4) {
  var className = _ref4.className,
      spanClassName = _ref4.spanClassName,
      onEditLinkClick = _ref4.onEditLinkClick,
      popupId = _ref4.popupId,
      props = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(_ref4, _excluded);

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("div", _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    className: classnames__WEBPACK_IMPORTED_MODULE_3___default()('block-editor-popup-trigger-popover__popup-viewer', className)
  }, props), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(PopupView, {
    popupId: popupId,
    className: spanClassName
  }), onEditLinkClick && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["IconButton"], {
    icon: "edit",
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Edit', 'popup-maker'),
    onClick: onEditLinkClick
  }));
}

/***/ }),

/***/ "./src/block-editor/formats/index.js":
/*!*******************************************!*\
  !*** ./src/block-editor/formats/index.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_rich_text__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/rich-text */ "@wordpress/rich-text");
/* harmony import */ var _wordpress_rich_text__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _popup_trigger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./popup-trigger */ "./src/block-editor/formats/popup-trigger/index.js");
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


[_popup_trigger__WEBPACK_IMPORTED_MODULE_1__].forEach(function (_ref) {
  var name = _ref.name,
      settings = _ref.settings;
  return Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_0__["registerFormatType"])(name, settings);
});

/***/ }),

/***/ "./src/block-editor/formats/popup-trigger/index.js":
/*!*********************************************************!*\
  !*** ./src/block-editor/formats/popup-trigger/index.js ***!
  \*********************************************************/
/*! exports provided: name, settings */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "name", function() { return name; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "settings", function() { return settings; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_rich_text__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/rich-text */ "@wordpress/rich-text");
/* harmony import */ var _wordpress_rich_text__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _icons_logo__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../icons/logo */ "./src/block-editor/icons/logo.js");
/* harmony import */ var _inline__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./inline */ "./src/block-editor/formats/popup-trigger/inline.js");








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */





/**
 * Internal dependencies
 */




var title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Popup Trigger', 'popup-maker');

var name = "popup-maker/popup-trigger";
var settings = {
  name: name,
  title: title,
  tagName: 'span',
  className: 'popup-trigger',
  attributes: {
    popupId: 'data-popup-id',
    doDefault: 'data-do-default'
  },
  edit: Object(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["withSpokenMessages"])( /*#__PURE__*/function (_Component) {
    _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(TriggerEdit, _Component);

    var _super = _createSuper(TriggerEdit);

    function TriggerEdit() {
      var _this;

      _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, TriggerEdit);

      _this = _super.apply(this, arguments);
      _this.addTrigger = _this.addTrigger.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
      _this.stopAddingTrigger = _this.stopAddingTrigger.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
      _this.onRemoveFormat = _this.onRemoveFormat.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
      _this.state = {
        addingTrigger: false
      };
      return _this;
    }

    _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(TriggerEdit, [{
      key: "addTrigger",
      value: function addTrigger() {
        this.setState({
          addingTrigger: true
        });
      }
    }, {
      key: "stopAddingTrigger",
      value: function stopAddingTrigger() {
        this.setState({
          addingTrigger: false
        });
      }
    }, {
      key: "onRemoveFormat",
      value: function onRemoveFormat() {
        var _this$props = this.props,
            value = _this$props.value,
            onChange = _this$props.onChange,
            speak = _this$props.speak;
        onChange(Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_8__["removeFormat"])(value, name));
        speak(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Trigger removed.', 'popup-maker'), 'assertive');
      }
    }, {
      key: "render",
      value: function render() {
        var _this$props2 = this.props,
            isActive = _this$props2.isActive,
            activeAttributes = _this$props2.activeAttributes,
            value = _this$props2.value,
            onChange = _this$props2.onChange;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10__["RichTextShortcut"], {
          type: "primary",
          character: "[",
          onUse: this.addTrigger
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10__["RichTextShortcut"], {
          type: "primaryShift",
          character: "[",
          onUse: this.onRemoveFormat
        }), isActive && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10__["RichTextToolbarButton"], {
          icon: _icons_logo__WEBPACK_IMPORTED_MODULE_11__["default"],
          title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Remove Trigger', 'popup-maker'),
          onClick: this.onRemoveFormat,
          isActive: isActive,
          shortcutType: "primaryShift",
          shortcutCharacter: "["
        }), !isActive && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_10__["RichTextToolbarButton"], {
          icon: _icons_logo__WEBPACK_IMPORTED_MODULE_11__["default"],
          title: title,
          onClick: this.addTrigger,
          isActive: isActive,
          shortcutType: "primary",
          shortcutCharacter: "["
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_inline__WEBPACK_IMPORTED_MODULE_12__["default"], {
          addingTrigger: this.state.addingTrigger,
          stopAddingTrigger: this.stopAddingTrigger,
          isActive: isActive,
          activeAttributes: activeAttributes,
          value: value,
          onChange: onChange
        }));
      }
    }]);

    return TriggerEdit;
  }(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]))
};

/***/ }),

/***/ "./src/block-editor/formats/popup-trigger/inline.js":
/*!**********************************************************!*\
  !*** ./src/block-editor/formats/popup-trigger/inline.js ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/keycodes */ "@wordpress/keycodes");
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/dom */ "@wordpress/dom");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/rich-text */ "@wordpress/rich-text");
/* harmony import */ var _wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./utils */ "./src/block-editor/formats/popup-trigger/utils.js");
/* harmony import */ var _components_trigger_popover__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../../components/trigger-popover */ "./src/block-editor/components/trigger-popover/index.js");
/* harmony import */ var _components_trigger_popover_popup_trigger_editor__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../../components/trigger-popover/popup-trigger-editor */ "./src/block-editor/components/trigger-popover/popup-trigger-editor.js");
/* harmony import */ var _components_trigger_popover_popup_trigger_viewer__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../../components/trigger-popover/popup-trigger-viewer */ "./src/block-editor/components/trigger-popover/popup-trigger-viewer.js");








var _excluded = ["isActive", "addingTrigger", "value"];


function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */






var stopKeyPropagation = function stopKeyPropagation(event) {
  return event.stopPropagation();
};

function isShowingInput(props, state) {
  return props.addingTrigger || state.editTrigger;
}

var TriggerPopoverAtText = function TriggerPopoverAtText(_ref) {
  var isActive = _ref.isActive,
      addingTrigger = _ref.addingTrigger,
      value = _ref.value,
      props = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_7___default()(_ref, _excluded);

  var anchorRect = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["useMemo"])(function () {
    var selection = window.getSelection();
    var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

    if (!range) {
      return;
    }

    if (addingTrigger) {
      return Object(_wordpress_dom__WEBPACK_IMPORTED_MODULE_12__["getRectangleFromRange"])(range);
    }

    var element = range.startContainer; // If the caret is right before the element, select the next element.

    element = element.nextElementSibling || element;

    while (element.nodeType !== window.Node.ELEMENT_NODE) {
      element = element.parentNode;
    }

    var closest = element.closest('span.popup-trigger');

    if (closest) {
      return closest.getBoundingClientRect();
    }
  }, [isActive, addingTrigger, value.start, value.end]);

  if (!anchorRect) {
    return null;
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_components_trigger_popover__WEBPACK_IMPORTED_MODULE_15__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_6___default()({
    anchorRect: anchorRect
  }, props));
};
/**
 * Generates a Popover with a select field to choose a popup, inline with the Rich Text editors.
 */


var InlinePopupTriggerUI = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(InlinePopupTriggerUI, _Component);

  var _super = _createSuper(InlinePopupTriggerUI);

  function InlinePopupTriggerUI() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, InlinePopupTriggerUI);

    _this = _super.apply(this, arguments);
    _this.editTrigger = _this.editTrigger.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.setPopupID = _this.setPopupID.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.setDoDefault = _this.setDoDefault.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.onFocusOutside = _this.onFocusOutside.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.submitTrigger = _this.submitTrigger.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.resetState = _this.resetState.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.state = {
      doDefault: false,
      popupId: ''
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(InlinePopupTriggerUI, [{
    key: "onKeyDown",
    value: function onKeyDown(event) {
      if ([_wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["LEFT"], _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["DOWN"], _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["RIGHT"], _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["UP"], _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["BACKSPACE"], _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["ENTER"]].indexOf(event.keyCode) > -1) {
        // Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
        event.stopPropagation();
      }
    }
  }, {
    key: "setPopupID",
    value: function setPopupID(popupId) {
      var noticeOperations = this.props.noticeOperations;
      noticeOperations.removeNotice('missingPopupId');

      if ('' === popupId) {
        noticeOperations.createNotice({
          id: 'missingPopupId',
          status: 'error',
          content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Choose a popup or the trigger won\'t function.', 'popup-maker')
        });
      }

      this.setState({
        popupId: popupId
      });
    }
  }, {
    key: "setDoDefault",
    value: function setDoDefault(doDefault) {
      var _this$props = this.props,
          _this$props$activeAtt = _this$props.activeAttributes.popupId,
          popupId = _this$props$activeAtt === void 0 ? 0 : _this$props$activeAtt,
          value = _this$props.value,
          onChange = _this$props.onChange;
      this.setState({
        doDefault: doDefault
      }); // Apply now if URL is not being edited.

      if (!isShowingInput(this.props, this.state)) {
        onChange(Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__["applyFormat"])(value, Object(_utils__WEBPACK_IMPORTED_MODULE_14__["createTriggerFormat"])({
          popupId: popupId,
          doDefault: doDefault
        })));
      }
    }
  }, {
    key: "editTrigger",
    value: function editTrigger(event) {
      this.setState({
        editTrigger: true
      });
      event.preventDefault();
    }
  }, {
    key: "submitTrigger",
    value: function submitTrigger(event) {
      var _this$props2 = this.props,
          isActive = _this$props2.isActive,
          value = _this$props2.value,
          onChange = _this$props2.onChange,
          speak = _this$props2.speak;
      var _this$state = this.state,
          popupId = _this$state.popupId,
          doDefault = _this$state.doDefault;
      var format = Object(_utils__WEBPACK_IMPORTED_MODULE_14__["createTriggerFormat"])({
        popupId: popupId,
        doDefault: doDefault
      });
      event.preventDefault();

      if (Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__["isCollapsed"])(value) && !isActive) {
        var toInsert = Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__["applyFormat"])(Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__["create"])({
          text: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Open Popup', 'popup-maker')
        }), format, 0, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Open Popup', 'popup-maker').length);
        onChange(Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__["insert"])(value, toInsert));
      } else {
        onChange(Object(_wordpress_rich_text__WEBPACK_IMPORTED_MODULE_13__["applyFormat"])(value, format));
      }

      this.resetState();

      if (isActive) {
        speak(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Trigger edited.', 'popup-maker'), 'assertive');
      } else {
        speak(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Trigger inserted.', 'popup-maker'), 'assertive');
      }
    }
  }, {
    key: "onFocusOutside",
    value: function onFocusOutside() {
      this.resetState();
    }
  }, {
    key: "resetState",
    value: function resetState() {
      this.props.stopAddingTrigger();
      this.setState({
        editTrigger: false
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      /**
       * @constant {boolean} isActive              True when the cursor is inside an existing trigger
       * @constant {boolean} addingTrigger         True when the user has clicked the add trigger button
       * @constant {Object}  activeAttributes      Object containing the current attribute values for the selected text.
       * @constant {Object}  value                 Object containing the current rich text selection object containing position & formats.
       * @constant {Object}  value.activeFormats   Array of registered & active WPFormat objects.
       * @constant {number}  value.formats         ?? Array of format history for the active text.
       * @constant {number}  value.start           Start offset of selected text
       * @constant {number}  value.end             End offset of selected text.
       * @constant {string}  value.text            Selected text.
       */
      var _this$props3 = this.props,
          isActive = _this$props3.isActive,
          addingTrigger = _this$props3.addingTrigger,
          value = _this$props3.value,
          noticeUI = _this$props3.noticeUI; // If the user is not adding a trigger from the toolbar or actively inside render nothing.

      if (!isActive && !addingTrigger) {
        return null;
      }

      var _this$state2 = this.state,
          popupId = _this$state2.popupId,
          doDefault = _this$state2.doDefault;
      var showInput = isShowingInput(this.props, this.state);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(TriggerPopoverAtText, {
        value: value,
        isActive: isActive,
        addingTrigger: addingTrigger,
        onFocusOutside: this.onFocusOutside,
        onClose: this.resetState,
        noticeUI: noticeUI,
        focusOnMount: showInput ? 'firstElement' : false,
        renderSettings: function renderSettings() {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["ToggleControl"], {
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Do default browser action?', 'popup-maker'),
            checked: doDefault,
            onChange: _this2.setDoDefault
          });
        }
      }, showInput ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_components_trigger_popover_popup_trigger_editor__WEBPACK_IMPORTED_MODULE_16__["default"], {
        className: "editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content",
        value: popupId,
        onChangeInputValue: this.setPopupID,
        onKeyDown: this.onKeyDown,
        onKeyPress: stopKeyPropagation,
        onSubmit: this.submitTrigger
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_components_trigger_popover_popup_trigger_viewer__WEBPACK_IMPORTED_MODULE_17__["default"], {
        className: "editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content",
        onKeyPress: stopKeyPropagation,
        popupId: popupId,
        onEditLinkClick: this.editTrigger // linkClassName=""

      }));
    }
  }], [{
    key: "getDerivedStateFromProps",
    value: function getDerivedStateFromProps(props, state) {
      var activeAttributes = props.activeAttributes;
      var _activeAttributes$pop = activeAttributes.popupId,
          popupId = _activeAttributes$pop === void 0 ? '' : _activeAttributes$pop;
      var _activeAttributes$doD = activeAttributes.doDefault,
          doDefault = _activeAttributes$doD === void 0 ? false : _activeAttributes$doD; // Convert string value to boolean for comparison.

      if (window._.isString(doDefault)) {
        doDefault = '1' === doDefault;
      }

      if (!isShowingInput(props, state)) {
        var update = {};

        if (popupId !== state.popupId) {
          update.popupId = popupId;
        }

        if (doDefault !== state.doDefault) {
          update.doDefault = doDefault;
        }

        return Object.keys(update).length ? update : null;
      }

      return null;
    }
  }]);

  return InlinePopupTriggerUI;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["withSpokenMessages"])(Object(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["withNotices"])(InlinePopupTriggerUI)));

/***/ }),

/***/ "./src/block-editor/formats/popup-trigger/utils.js":
/*!*********************************************************!*\
  !*** ./src/block-editor/formats/popup-trigger/utils.js ***!
  \*********************************************************/
/*! exports provided: createTriggerFormat */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createTriggerFormat", function() { return createTriggerFormat; });
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./index */ "./src/block-editor/formats/popup-trigger/index.js");
/**
 * Internal dependencies
 */

/**
 * Generates the format object that will be applied to the trigger text.
 *
 * @param {Object}  options
 * @param {number}  options.popupId       The popup ID.
 * @param {boolean} options.doDefault     Whether this trigger will act normally when clicked.
 *
 * @return {Object} The final format object.
 */

function createTriggerFormat(_ref) {
  var _ref$popupId = _ref.popupId,
      popupId = _ref$popupId === void 0 ? 0 : _ref$popupId,
      _ref$doDefault = _ref.doDefault,
      doDefault = _ref$doDefault === void 0 ? false : _ref$doDefault;
  var doDefaultClass = doDefault ? 'pum-do-default' : '';
  return {
    type: _index__WEBPACK_IMPORTED_MODULE_0__["name"],
    attributes: {
      class: "popmake-".concat(popupId, " ").concat(doDefaultClass),
      popupId: "".concat(popupId),
      doDefault: doDefault ? '1' : '0'
    }
  };
}

/***/ }),

/***/ "./src/block-editor/icons/gears.js":
/*!*****************************************!*\
  !*** ./src/block-editor/icons/gears.js ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

var GearsIcon = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('svg', {
  viewBox: '0 0 512 512',
  width: 20,
  height: 20
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M348,327.195v-35.741l-32.436-11.912c-2.825-10.911-6.615-21.215-12.216-30.687l0.325-0.042l15.438-32.153l-25.2-25.269  l-32.118,15.299l-0.031,0.045c-9.472-5.601-19.758-9.156-30.671-11.978L219.186,162h-35.739l-11.913,32.759  c-10.913,2.821-21.213,6.774-30.685,12.379l-0.048-0.248l-32.149-15.399l-25.269,25.219l15.299,32.124l0.05,0.039  c-5.605,9.471-11.159,19.764-13.98,30.675L50,291.454v35.741l34.753,11.913c2.821,10.915,7.774,21.211,13.38,30.685l0.249,0.045  l-15.147,32.147l25.343,25.274l32.188-15.298l0.065-0.046c9.474,5.597,19.782,10.826,30.695,13.652L183.447,460h35.739  l11.915-34.432c10.913-2.826,21.209-7.614,30.681-13.215l0.05-0.175l32.151,15.192l25.267-25.326l-15.299-32.182l-0.046-0.061  c5.601-9.473,8.835-19.776,11.66-30.688L348,327.195z M201.318,368.891c-32.897,0-59.566-26.662-59.566-59.565  c0-32.896,26.669-59.568,59.566-59.568c32.901,0,59.566,26.672,59.566,59.568C260.884,342.229,234.219,368.891,201.318,368.891z'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M462.238,111.24l-7.815-18.866l-20.23,1.012c-3.873-5.146-8.385-9.644-13.417-13.42l0.038-0.043l1.06-20.318l-18.859-7.822  L389.385,66.89l-0.008,0.031c-6.229-0.883-12.619-0.933-18.988-0.025L356.76,51.774l-18.867,7.815l1.055,20.32  c-5.152,3.873-9.627,8.422-13.403,13.46l-0.038-0.021l-20.317-1.045l-7.799,18.853l15.103,13.616l0.038,0.021  c-0.731,5.835-1.035,12.658-0.133,19.038l-15.208,13.662l7.812,18.87l20.414-1.086c3.868,5.144,8.472,9.613,13.495,13.385  l0.013,0.025l-1.03,20.312l20.668,7.815L374,201.703v-0.033c4,0.731,10.818,0.935,17.193,0.04l12.729,15.114l18.42-7.813  l-1.286-20.324c5.144-3.875,9.521-8.424,13.297-13.456l-0.023,0.011l20.287,1.047l7.802-18.864l-15.121-13.624l-0.033-0.019  c0.877-6.222,0.852-12.58-0.05-18.953L462.238,111.24z M392.912,165.741c-17.359,7.19-37.27-1.053-44.462-18.421  c-7.196-17.364,1.047-37.272,18.415-44.465c17.371-7.192,37.274,1.053,44.471,18.417  C418.523,138.643,410.276,158.547,392.912,165.741z'
}));
/* harmony default export */ __webpack_exports__["default"] = (GearsIcon);

/***/ }),

/***/ "./src/block-editor/icons/logo.js":
/*!****************************************!*\
  !*** ./src/block-editor/icons/logo.js ***!
  \****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

var LogoIcon = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('svg', {
  viewBox: '0 0 106 84',
  width: 24,
  height: 24,
  className: 'popup-trigger-button-svg'
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 74.98 0.00 L 80.18 0.00 C 86.85 0.96 93.11 3.19 97.92 8.09 C 102.82 12.91 105.07 19.19 106.00 25.89 L 106.00 29.25 C 105.01 36.93 101.84 43.76 95.96 48.90 C 85.62 57.23 75.10 65.38 64.88 73.86 C 58.14 79.85 49.63 82.94 40.76 84.00 L 36.17 84.00 C 27.56 83.00 19.39 80.03 12.89 74.16 C 5.17 67.38 1.08 57.89 0.00 47.78 L 0.00 43.19 C 1.06 33.34 4.97 24.08 12.35 17.32 C 19.55 10.62 29.39 7.33 38.98 6.07 C 50.98 4.07 63.06 2.41 74.98 0.00 Z',
  fill: '#98b729'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 73.27 3.38 C 78.51 2.46 83.84 3.16 88.72 5.25 C 99.12 9.98 105.12 21.94 102.29 33.09 C 100.93 39.34 97.06 44.25 92.19 48.20 C 84.32 54.30 76.63 60.62 68.82 66.78 C 65.27 69.54 61.99 72.75 58.21 75.17 C 53.04 78.31 47.09 80.42 41.04 80.90 C 26.64 81.98 12.34 73.74 6.37 60.53 C 0.78 48.69 2.33 34.56 10.17 24.12 C 16.07 16.10 25.11 11.68 34.69 9.75 C 47.55 7.61 60.45 5.72 73.27 3.38 Z',
  fill: '#262d2b'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 73.39 7.40 C 79.51 6.31 85.83 7.34 90.84 11.17 C 97.78 16.34 100.76 25.75 97.94 33.97 C 96.07 39.49 92.17 43.26 87.63 46.67 C 80.70 52.04 73.92 57.62 67.04 63.05 C 61.52 67.32 57.24 72.00 50.55 74.56 C 39.66 79.19 26.67 77.04 17.82 69.21 C 10.09 62.55 6.01 52.13 7.21 41.99 C 8.21 32.78 13.46 24.27 21.21 19.22 C 29.30 14.01 37.69 13.29 46.90 11.83 C 55.73 10.34 64.58 9.05 73.39 7.40 Z',
  fill: '#98b729'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 79.33 11.15 C 80.91 11.34 82.49 11.77 84.05 12.13 C 83.96 13.78 83.90 15.42 83.83 17.07 C 85.21 18.44 86.59 19.81 87.96 21.19 C 89.56 21.12 91.16 21.05 92.76 20.97 C 93.19 22.58 93.62 24.19 94.07 25.79 C 92.62 26.56 91.18 27.34 89.74 28.11 C 89.27 30.00 88.80 31.89 88.29 33.77 C 89.17 35.11 90.05 36.46 90.93 37.80 C 89.75 38.99 88.56 40.18 87.37 41.36 C 86.03 40.50 84.69 39.65 83.36 38.79 C 81.43 39.31 79.50 39.83 77.57 40.33 C 76.86 41.76 76.14 43.18 75.44 44.61 C 73.84 44.14 72.22 43.70 70.60 43.30 C 70.70 41.70 70.79 40.09 70.89 38.49 C 69.46 37.08 68.05 35.65 66.64 34.22 C 65.07 34.33 63.50 34.41 61.94 34.52 C 61.54 32.88 61.09 31.25 60.61 29.63 C 62.04 28.92 63.45 28.20 64.87 27.48 C 65.38 25.56 65.93 23.65 66.45 21.74 C 65.57 20.37 64.69 19.01 63.80 17.65 C 64.99 16.46 66.17 15.27 67.36 14.08 C 68.70 14.97 70.04 15.86 71.38 16.75 C 73.20 16.26 75.02 15.78 76.84 15.32 C 77.62 13.91 78.39 12.46 79.33 11.15 Z',
  fill: '#262d2b'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 31.46 18.53 C 35.73 17.41 39.75 17.90 44.06 18.38 C 43.69 20.25 43.38 22.13 43.00 23.99 C 46.30 25.32 49.40 26.46 52.10 28.89 C 56.07 32.21 58.00 36.65 59.46 41.49 C 61.32 41.26 63.19 41.04 65.06 40.81 C 65.30 45.35 65.55 49.64 64.02 54.02 C 62.82 57.89 60.52 60.95 58.09 64.10 C 56.66 62.88 55.24 61.65 53.81 60.43 C 50.80 62.88 47.90 65.17 44.07 66.21 C 39.50 67.65 35.11 67.00 30.55 65.99 C 29.84 67.72 29.12 69.46 28.40 71.19 C 24.48 69.34 20.78 67.44 17.87 64.12 C 14.90 61.08 13.34 57.40 11.80 53.51 C 13.55 52.89 15.31 52.27 17.06 51.65 C 16.43 47.16 15.95 42.88 17.48 38.49 C 18.70 34.52 21.22 31.56 23.95 28.54 C 22.80 27.05 21.69 25.54 20.55 24.05 C 23.99 21.67 27.30 19.46 31.46 18.53 Z',
  fill: '#262d2b'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 76.34 24.32 C 79.21 23.52 81.89 26.79 80.48 29.46 C 79.35 31.71 76.40 32.21 74.62 30.38 C 72.72 28.34 73.67 25.06 76.34 24.32 Z',
  fill: '#98b729'
}), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])('path', {
  d: 'M 33.46 26.53 C 40.08 24.87 47.25 27.17 51.85 32.16 C 57.28 37.94 58.59 46.87 54.94 53.94 C 51.18 61.61 42.36 65.97 33.97 64.14 C 25.47 62.43 18.97 54.70 18.77 46.02 C 18.32 36.96 24.64 28.60 33.46 26.53 Z',
  fill: '#98b729'
}));
/* harmony default export */ __webpack_exports__["default"] = (LogoIcon);

/***/ }),

/***/ "./src/block-editor/index.js":
/*!***********************************!*\
  !*** ./src/block-editor/index.js ***!
  \***********************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _formats__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./formats */ "./src/block-editor/formats/index.js");
/* harmony import */ var _block_extensions__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block-extensions */ "./src/block-editor/block-extensions/index.js");
/*******************************************************************************
 * Copyright (c) 2020, Code Atlantic LLC.
 ******************************************************************************/



/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["blockEditor"]; }());

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ "@wordpress/compose":
/*!*********************************!*\
  !*** external ["wp","compose"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ "@wordpress/dom":
/*!*****************************!*\
  !*** external ["wp","dom"] ***!
  \*****************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["dom"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/hooks":
/*!*******************************!*\
  !*** external ["wp","hooks"] ***!
  \*******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["hooks"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ "@wordpress/keycodes":
/*!**********************************!*\
  !*** external ["wp","keycodes"] ***!
  \**********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["keycodes"]; }());

/***/ }),

/***/ "@wordpress/rich-text":
/*!**********************************!*\
  !*** external ["wp","richText"] ***!
  \**********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["richText"]; }());

/***/ })

/******/ });
//# sourceMappingURL=block-editor.js.map