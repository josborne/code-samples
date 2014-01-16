$(document).ready(function ()
{
    function ViewModel(config)
    {
        var self = this;
        this.deleteProduct = ko.observable(null);
        // initial call to mapping to create the object properties
        ko.mapping.fromJS(config, {}, self);
    }

    function UpdateViewModel(vm)
    {
        $.getJSON(base_url + 'dashboard/get_dashboard_data', function (data)
        {
            ko.mapping.fromJS(data, {}, vm);
        });
    }

    var vm = new ViewModel({totalProducts: 0, totalOwed: 0, products: [], notifications: [], promoting: [], beingPromoted: []});
    UpdateViewModel(vm);


    //extend functions
    vm.onViewPromoted = function (userPromote)
    {
        $.getJSON(base_url + 'promote/get_user_promote_json', {id: userPromote.userPromoteID()}, function (data)
        {
            $('#viewUserPromoteID').val(userPromote.userPromoteID());
            $('#viewPromoteUser').text(userPromote.username());
            $('#viewPromoteDetails').text(data.doneDetails);
            $('#viewPromoteModal').modal('show');
        });
    };

    vm.onConfirmPromoted = function ()
    {
        var id = $('#viewUserPromoteID').val();

        $.ajax({
            type: "POST",
            url: base_url + 'promote/owner_confirm_promoted',
            data: {id: id},
            cache: false,
            dataType: "json",
            success: function ()
            {
                $.jGrowl("Thanks for confirming", { header: 'Review Confirmed' });
                UpdateViewModel(vm);
                $('#viewPromoteModal').modal('hide');
            }
        });
    };

    vm.onMarkPromotingComplete = function (promoting)
    {
        window.location = base_url + 'promote/product/' + promoting.slug();
    };

    //TODO: implement canceling promotion
    vm.onCancelPromoting = function (promoting)
    {
        $.jGrowl("Cancelling promotion is currently in development.", { header: 'In Development' });
    };

    vm.onReadNotification = function (notification)
    {
        if (notification.isRead() == 1) return;

        $.ajax({
            type: "POST",
            url: base_url + 'dashboard/mark_notification_read',
            data: {id: notification.notificationID()},
            cache: false,
            success: function (data)
            {
                $('#notificationCount').text(data);
                UpdateViewModel(vm);
            }
        });
    };

    vm.onDeleteNotification = function (notification)
    {
        $.ajax({
            type: "POST",
            url: base_url + 'dashboard/delete_notification',
            data: {id: notification.notificationID()},
            cache: false,
            success: function (data)
            {
                $('#notificationCount').text(data);
                UpdateViewModel(vm);
            }
        });
    };

    vm.onReadAllNotifications = function ()
    {
        $.ajax({
            type: "POST",
            url: base_url + 'dashboard/mark_all_notification_read',
            cache: false,
            success: function ()
            {
                $('#notificationCount').text('');
                UpdateViewModel(vm);
            }
        });
    };

    vm.onEditProduct = function (product)
    {
        window.location = base_url + 'products/edit/' + product.slug();
    };

    vm.onRemoveProduct = function (product)
    {
        vm.deleteProduct(product);
        $('#removeProductModal').modal('show');
    };

    vm.onConfirmRemoveProduct = function ()
    {
        var productID = vm.deleteProduct().productID();

        $.ajax({
            type: "POST",
            url: base_url + 'products/remove_product',
            data: {productID: productID},
            cache: false,
            success: function ()
            {
                $.jGrowl("Product has been removed", { header: 'Success' });
                UpdateViewModel(vm);
                $('#removeProductModal').modal('hide');
                vm.deleteProduct(null);
            }
        });

    };

    $('#reviewHelp').popover();

    ko.applyBindings(vm);
});
