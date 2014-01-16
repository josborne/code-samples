jQuery(document).ready(function ($)
{
    $('#pwyl_customAmountView').hide();
    $('#showLoading').hide();
    $(document).data("submitted", false);

    $('input[name=pwyl_paymentAmount]').click(function ()
    {
        if ($(this).val() == 0)
        {
            $('#pwyl_customAmountView').show();
        }
    });

    /**
     * @return {boolean}
     */
    function IsEmail(email)
    {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }

    var handler = StripeCheckout.configure({
        key: stripekey,
        token: function (token, args)
        {
            var $form = $('#pwyl-form');
            var $err = $(".pwyl_payment_errors");

            $(document).data("submitted", true);

            $form.append("<input type='hidden' name='stripeToken' value='" + token.id + "' />");
            var amount = $(document).data('pwyl_amount');
            $form.append("<input type='hidden' name='pwyl_total_amount' value='" + amount + "'/>");

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: $form.serialize(),
                cache: false,
                dataType: "json",
                success: function (data)
                {
                    if (data.success)
                    {
                        $err.addClass('pwyl_success');
                        $err.html(data.msg);

                        //now download the file
                        $("#pwyl_downloadFile").append(data.pwyl);

                        //redirect if requested
                        if (pwyl_form_options.show_thanks == 1)
                        {
                            setTimeout(function ()
                            {
                                window.location = pwyl_form_options.thankyou_page;
                            }, pwyl_form_options.redirect_delay);
                        }

                        $('#showLoading').hide();

                    }
                    else
                    {
                        $err.addClass('pwyl_error');
                        $err.html(data.msg);
                        $form.find('button').prop('disabled', false);
                        $('#showLoading').hide();
                    }
                }
            });
        },
        closed: function ()
        {
            if (!$(document).data("submitted"))
            {
                $('#pwyl-form').find('button').prop('disabled', false);
                $('#showLoading').hide();
            }
        }
    });

    $('#pwyl-form').submit(function (e)
    {
        e.preventDefault();
        var $form = $('#pwyl-form');
        var $err = $('.pwyl_payment_errors');
        var $loader = $('#showLoading');
        $form.find('button').prop('disabled', true);
        $loader.show();
        $err.removeClass("pwyl_error");
        $err.removeClass("pwyl_success");
        $err.html("");

        var amount = $('input[name=pwyl_paymentAmount]:checked', '#pwyl-form').val();
        if (amount == 0)
        {
            amount = $('#pwyl_customAmount').val();
            //validate:  is it a valid number?
            if (amount.length == 0 || amount.match(/[^0-9\.\$]/g)) //regex checks if it's anything other than 0-9, decimal and $
            {
                $err.addClass("pwyl_error");
                $err.html("Please enter a valid amount");
                $form.find('button').prop('disabled', false);
                $loader.hide();
                return false;
            }

            //if there is no decimal point, add the .00 so we can get the cents value easier
            if (amount.indexOf('.') == -1)
            {
                amount += ".00";
            }
            //now strip everything but numbers, thereby leaving the amount in cents
            amount = amount.replace(/[^\d]/g, '');

            //check against the minimum amount (this check is also done on the server)
            if (parseInt(amount) < parseInt(pwyl_form_options.min_price))
            {
                $err.addClass("pwyl_error");
                $err.html("Please enter more than the minimum amount");
                $form.find('button').prop('disabled', false);
                $loader.hide();
                return false;
            }
        }

        //validate email address
        var email = $('#pwyl_emailAddress').val();
        if (!IsEmail(email))
        {
            $err.addClass("pwyl_error");
            $err.html("Please enter a valid email address");
            $form.find('button').prop('disabled', false);
            $loader.hide();
        }
        else
        {
            $(document).data('pwyl_amount', amount);

            handler.open({
                name: pwyl_form_options.seller_name,
                description: pwyl_form_options.product_name,
                amount: amount,
                email: email,
                billingAddress: (pwyl_form_options.validate_address == 1),
                image: pwyl_form_options.image_url
            });
        }

        return false;
    });
});