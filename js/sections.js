/**
 * @package    olas
 * @author     Cédric Denis, Gilles Dubois
 * @copyright  Copyright (c) 2010-2015 FactorFX, Linagora
 * @license    AGPL License 3.0 or (at your option) any later version
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link       https://www.factorfx.com
 * @link       http://www.linagora.com
 * @since      2015
 *
 * --------------------------------------------------------------------------
 */

$(function () {
    var allSection = $("[name^='section']"),
        fire = $("[name^='details']");

    refreshSections();
    showHideSections(fire, allSection);
    showNextSection();
});

function showHideSections(fire, allSection) {
    var show = function () {
        if (fire.val() == 0) {
            hideAll(allSection);
        }
        else {
            showNext(allSection.first());
        }
    };
    show();
    fire.on('change', function () {
        show();
    });
}

function showNextSection() {
    var imgSEction = $("img[id*='_section_']"),
        regexAdd = /^add_/,
        regexSub = /^sub_/;

    imgSEction.on('click', function () {
        var idImgClicked = $(this).attr('id');
        if (idImgClicked.match(regexAdd)) {
            $(this).closest('tr').next("[id^='section_']").show();
        }
        else if (idImgClicked.match(regexSub)) {
            var id = $(this).attr('id').replace('sub_section_', '');
            console.log('dropdown_sections_' + id);
            $('#dropdown_sections_' + id).val(0).trigger('change');
            $(this).closest('tr').hide().nextAll("[id^='section_']").hide();
        }
    });
}

function refreshSections() {

}

function hideAll(allSection) {
    allSection.each(function () {
        $(this).closest("tr[id^='section_']").hide();
    });
}

function showNext(section) {
    section.closest("tr[id^='section_']").show();
}

function toggleTable(id) {
    var lTable = document.getElementById(id);
    lTable.style.display = (lTable.style.display == "table") ? "show" : "table";
}

function hideTable(id) {
    var lTable = document.getElementById(id);
    lTable.style.display = (lTable.style.display == "table") ? "none" : "table";
}

function getQueryVariable(variable)
{
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        if(pair[0] == variable){return pair[1];}
    }
    return(false);
}

function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
        return uri + separator + key + "=" + value;
    }
}

function reloadWithCsv(value) {

    var param = getQueryVariable('csv');

    if(param != false){
        var new_url = updateQueryStringParameter(window.location.href,'csv',value);
        location.assign(new_url);
    }else{
        str = "&";
        str += "csv=" + value;
        location.assign(location.origin + location.pathname + location.search + str + location.hash);
    }

};

