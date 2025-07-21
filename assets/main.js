import 'jquery';
//import $ from "jquery"; // recommended code not used - I made it globally available

// Datepicker - Charts page
import 'jquery-ui/ui/widgets/datepicker';

// Bootstrap - probably not required, but using bootstrap theme
import 'bootstrap/dist/js/bootstrap.js';

import netteForms from 'nette-forms';
netteForms.initOnLoad();

//import 'nette.ajax.js/nette.ajax.js'; // replaced nette.ajax.js for naja
import naja from 'naja'; // required by: datagrid
//naja.initialize(); // recommended code not used - I made it globally available (because of datagrid 6.9.1)

// Assets for older version of datagrid (6.9.1)
// This project is very simple and doesn't need more than Inline Edit.
import 'ublaboo-datagrid/assets/datagrid.js';
// Current assets - Inline Edit doesn't work, needs manual activation and configuration,
// but I'm unsure how to do it properly.
// import '@contributte/datagrid/dist/datagrid-full.js';
// import '@contributte/datagrid/assets/datagrid-all.ts';


import './js/custom.js'


// - - - - - - - - - - CSS - - - - - - - - - -

import 'jquery-ui/themes/base/all.css';

import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-theme.css';

// Assets for older version of datagrid (6.9.1)
import 'ublaboo-datagrid/assets/datagrid.css';
// Current assets - would break datepicker on Charts page
//import '@contributte/datagrid/dist/datagrid-full.css';


import './css/style.css'
