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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/integration/ninjaforms.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/integration/ninjaforms.js":
/*!*************************************************!*\
  !*** ./assets/js/src/integration/ninjaforms.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/
{
  var $ = window.jQuery;
  var pumNFController = false;

  initialize_nf_support = function initialize_nf_support() {
    /** Ninja Forms Support */
    if (typeof Marionette !== 'undefined' && typeof nfRadio !== 'undefined' && false === pumNFController) {
      pumNFController = Marionette.Object.extend({
        initialize: function initialize() {
          this.listenTo(nfRadio.channel('forms'), 'submit:response', this.popupMaker);
        },
        popupMaker: function popupMaker(response, textStatus, jqXHR, formID) {
          var form = document.getElementById('#nf-form-' + formID + '-cont'),
              $form = $(form),
              settings = {};

          if (response.errors.length) {
            return;
          }

          window.PUM.integrations.formSubmission(form, {
            formProvider: 'ninjaforms',
            formID: formID,
            formKey: 'ninjaforms' + '_' + formID,
            response: response
          });
          debugger; // Listen for older popup actions applied directly to the form.

          if ('undefined' !== typeof response.data.actions) {
            settings.openpopup = 'undefined' !== typeof response.data.actions.openpopup;
            settings.openpopup_id = settings.openpopup ? parseInt(response.data.actions.openpopup) : 0;
            settings.closepopup = 'undefined' !== typeof response.data.actions.closepopup;
            settings.closedelay = settings.closepopup ? parseInt(response.data.actions.closepopup) : 0;

            if (settings.closepopup && response.data.actions.closedelay) {
              settings.closedelay = parseInt(response.data.actions.closedelay);
            }
          } // Nothing should happen if older action settings not applied
          // except triggering of pumFormSuccess event.


          window.PUM.forms.success($form, settings);
        }
      }); // Initialize it.

      new pumNFController();
    }
  };

  $(document).ready(initialize_nf_support);
}

/***/ })

/******/ });