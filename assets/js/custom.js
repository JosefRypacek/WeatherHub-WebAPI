// Actions on page load
$(document).ready(function () {
    chartsInitChartRedraw();
    chartsConfigureDatepicker();
});



// Only related to Charts page
// Configure Datepicker used in form to select date.
function chartsConfigureDatepicker() {
    // Enable this code only if there is speficif form field used to select date.
    if ($('#frm-dateForm-from.datepicker').length === 0) {
        return;
    }

    $('input.datepicker').datepicker({
        firstDay: 1,
        changeMonth: true,
        changeYear: true,
        //dateFormat: 'mm/dd/yy',
        dateFormat: 'dd.mm.yy',
        yearRange: '2016:2026'
    });

    $.datepicker.regional['cs'] = {
        closeText: 'Cerrar',
        prevText: 'Předchozí',
        nextText: 'Další',
        currentText: 'Hoy',
        monthNames: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
        monthNamesShort: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
        //monthNamesShort: ['Le', 'Ún', 'Bř', 'Du', 'Kv', 'Čn', 'Čc', 'Sr', 'Zá', 'Ří', 'Li', 'Pr'],
        dayNames: ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'],
        dayNamesShort: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So',],
        dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
        weekHeader: 'Sm',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''
    };

    $.datepicker.setDefaults($.datepicker.regional['cs']);
}



// Only related to Charts page
// This code can't be in HTML body as jQuery is not available there when loaded using Vite.
// Ideally the rest of the JavaScript code should be there as well, but it's being dynamically
// generated and it would have to be splitted so only definiton of variable would remain in the HTML page.
function chartsInitChartRedraw() {
    // Enable this code only on Charts page.
    if ($('#curve_chart_all').length === 0) {
        return;
    }

    var wWidth = $(window).width(); // initial width

    // create trigger to resizeEnd event - wait 500ms before redraw
    $(window).resize(function () {
        if (this.resizeTO) {
            clearTimeout(this.resizeTO);
        }
        this.resizeTO = setTimeout(function () {
            $(this).trigger('resizeEnd');
        }, 500);
    });

    // redraw graph when window resize is completed  
    $(window).on('resizeEnd', function () {
        var oldWidth = wWidth;
        wWidth = $(window).width();
        if (oldWidth === wWidth) {
            return; // do NOT handle height change
        }

        if (google.visualization === undefined) {
            return; // google lib not loaded yet
        }

        var r = confirm("Změnila se velikost stránky. Chceš překreslit grafy?");
        if (r == true) {
            drawChart(true);
        }
    });
}
