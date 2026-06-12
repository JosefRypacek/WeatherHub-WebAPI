// Actions on page load
$(document).ready(function () {
    chartsInitChartRedraw();
    setupNativeDatepickerTrigger();
});

// Open browser-native date picker popup when clicking anywhere inside the input
function setupNativeDatepickerTrigger() {
    $(document).on('click', 'input.datepicker', function () {
        try {
            this.showPicker();
        } catch (err) {
            // fallback for older browsers
        }
    });
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
    var resizeTO;

    // create trigger to resizeEnd event - wait 500ms before redraw
    $(window).resize(function () {
        if (resizeTO) {
            clearTimeout(resizeTO);
        }
        resizeTO = setTimeout(function () {
            $(window).trigger('resizeEnd');
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
