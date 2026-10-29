/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
require(['jquery'], function ($) {
    'use strict';

    function getFormKey() {
        if (window.FORM_KEY) {
            return window.FORM_KEY;
        }
        var $input = $('input[name="form_key"]');
        return $input.length ? $input.val() : '';
    }

    function renderPaytmResponseRows($tbody, rows) {
        $tbody.empty();
        if (!rows || !rows.length) {
            return;
        }
        rows.forEach(function (row) {
            var $tr = $('<tr>');
            if (row.emphasis) {
                $tr.css('font-weight', '900');
            }
            $tr.append($('<th>').text(row.key));
            $tr.append($('<td>').text(row.value));
            $tbody.append($tr);
        });
    }

    $(document).on('click', '.fetchStatusBtn', function (event) {
        event.preventDefault();
        var currentURL = $(this).attr('moveURL');
        if (!currentURL) {
            return false;
        }
        $.ajax({
            showLoader: true,
            url: currentURL,
            type: 'GET',
            dataType: 'json',
            data: {
                form_key: getFormKey(),
                isAjax: true
            }
        }).done(function (data) {
            if (data && data.error) {
                alert(data.message || 'Request failed.');
                return;
            }
            renderPaytmResponseRows($('.paytmResponseTBody'), data.responseRows);
            if (data.response) {
                $('.fetchStatusBtn').remove();
            }
        }).fail(function () {
            alert('Unable to fetch Paytm status. Please refresh the order page and try again.');
        });
        return false;
    });
});
