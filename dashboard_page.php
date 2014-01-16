<?php require('header.php'); ?>
    <script type="text/javascript">
        window.base_url = "<?php echo site_url(); ?>";
    </script>
    <script type="text/javascript" src="<?php echo site_url('assets/js') . '/knockout.mapping.js'; ?>"></script>
    <script type="text/javascript" src="<?php echo site_url('assets/js/app') . '/dashboard_page.js'; ?>"></script>

    <div id="content" class="container col-md-12">
        <?php if ($this->session->flashdata('message')) : ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <?php echo $this->session->flashdata('message'); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div data-bind="if: totalOwed() < -1 && totalOwed() > -5">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        You owe reviews! Please give reviews to other users products. If your review value gets too low your products will be de-activated.
                    </div>
                </div>
            </div>
        </div>
        <div data-bind="if: totalOwed() <= -5">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        You owe too many reviews! Your products have been de-activated and unable to receive more reviews. Please review other users products to improve your review value.
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="dashboard-stat">
                        You have
                        <span class="dashboard-number" data-bind="text: totalProducts">0</span> products requesting reviews
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="dashboard-stat">
                        You are owed
                        <span class="dashboard-number" data-bind="text: totalOwed">0</span> product review(s)
                        <a id="reviewHelp" href="#" data-toggle="popover" title="Review Value"
                           data-content="A positive value means you are owed reviews.  A negative value means you must give more reviews.  If you owe too many reviews, your products will go inactive and unable to receive more reviews until you improve your ratio.">
                            <span class="glyphicon glyphicon-question-sign"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Your Products</div>
                    <div style="overflow: auto; max-height: 300px; height: 300px;">
                        <table class="table">
                            <tbody data-bind="foreach: products">
                            <tr>
                                <td class="col-md-4"><a data-bind="attr: {href: viewURL}, text: name"></a></td>
                                <td class="col-md-1"><span data-bind="text: reviews"></span> reviews</td>
                                <td class="col-md-1">
                                    <button class="btn btn-warning btn-xs" title="Edit product" data-bind="click: $root.onEditProduct">
                                        <span class="glyphicon glyphicon-pencil"></span>
                                    </button>
                                    <button class="btn btn-danger btn-xs" title="Remove product" data-bind="click: $root.onRemoveProduct">
                                        <span class="glyphicon glyphicon-remove"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Promoting</div>
                    <div style="overflow: auto; max-height: 300px;  height: 300px;">
                        <table class="table">
                            <tbody data-bind="foreach: promoting">
                            <tr>
                                <td class="col-md-5">
                                    <div data-bind="if: done()==0">You have agreed to promote
                                        <a data-bind="attr: {href: viewURL}, text: name"></a></div>
                                    <div data-bind="if: done()==1">You have promoted
                                        <a data-bind="attr: {href: viewURL}, text: name"></a>. Waiting for confirmation.
                                    </div>
                                </td>
                                <td class="col-md-1">
                                    <div data-bind="if: done()==0">
                                        <button class="btn btn-success btn-xs" title="Mark as complete" data-bind="click: $root.onMarkPromotingComplete">
                                            <span class="glyphicon glyphicon-ok"></span>
                                        </button>
                                        <button class="btn btn-danger btn-xs" title="Cancel promoting" data-bind="click: $root.onCancelPromoting">
                                            <span class="glyphicon glyphicon-remove"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" style="padding-top: 20px;">
            <div class="col-md-6" id="notifications">
                <div class="panel panel-default">
                    <div class="panel-heading">Notifications
                    </div>
                    <div style="overflow: auto; max-height: 300px; height: 300px;">
                        <table class="table">
                            <tbody data-bind="foreach: notifications">
                            <tr>
                                <td class="col-md-5" data-bind="html: notification, css: { 'unread-notification': isRead() == 0 }"></td>
                                <td class="col-md-1">
                                    <button class="btn btn-success btn-xs" title="Mark as read" data-bind="click: $root.onReadNotification">
                                        <span class="glyphicon glyphicon-ok"></span></button>
                                    <button class="btn btn-danger btn-xs" title="Delete notification" data-bind="click: $root.onDeleteNotification">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <a class="btn btn-primary btn-block" href="<?php echo site_url('promote'); ?>">Add Product</a>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Being Promoted</div>
                    <div style="overflow: auto; max-height: 300px;  height: 300px;">
                        <table class="table">
                            <tbody data-bind="foreach: beingPromoted">
                            <tr>
                                <td class="col-md-5">
                                    <div data-bind="if: done()==0">
                                        <a data-bind="attr: {href: userURL}, text: username"></a> is promoting
                                        <a data-bind="attr: {href: productURL}, text: name"></a>
                                    </div>
                                    <div data-bind="if: done()==1">
                                        <a data-bind="attr: {href: userURL}, text: username"></a> has finished promoting
                                        <a data-bind="attr: {href: productURL}, text: name"></a>!
                                    </div>
                                </td>
                                <td class="col-md-1">
                                    <div data-bind="if: done()==1">
                                        <button class="btn btn-success btn-xs" data-bind="click: $root.onViewPromoted">View</button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <a class="btn btn-primary btn-block" href="<?php echo site_url('products'); ?>">Browse All Products</a>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="viewPromoteModal" tabindex="-1" role="dialog" aria-labelledby="viewPromoteModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Product Reviewed</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="viewUserPromoteID">
                    <p><span id="viewPromoteUser"></span> has reviewed your product. Details below:</p>
                    <p id="viewPromoteDetails" class="text-success"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bind="click: onConfirmPromoted">Confirm This Review</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="removeProductModal" tabindex="-1" role="dialog" aria-labelledby="removeProductModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Remove Product</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you wish to <strong>remove</strong> this product?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bind="click: onConfirmRemoveProduct">Yes, Remove this product</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
<?php require('footer.php'); ?>