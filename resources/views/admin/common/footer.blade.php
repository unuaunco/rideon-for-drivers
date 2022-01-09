<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Confirm Delete</h4>
                </div>
                <div class="modal-body">
                    <p>You are about to delete one track, this procedure is irreversible.</p>
                    <p>Do you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default confirm-delete_cancel" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger btn-ok confirm-delete">Delete</a>
                </div>
            </div>
        </div>
</div>

<div class="modal fade" id="confirm-active" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Confirm Active</h4>
                </div>
                <div class="modal-body">
                    <p>You are about to active one application.</p>
                    <p>Do you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default confirm-active_cancel" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger btn-ok confirm-active">Active</a>
                </div>
            </div>
        </div>
</div>

<div class="modal fade" id="confirm-suspend" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel2">Confirm Order Payout Suspend</h4>
            </div>
            <div class="modal-body">
                <p>You are about to suspend payout for driver on this order.</p>
                <form>
                    <div class="form-group">
                      <label for="message-text" class="col-form-label">Suspend Reason (will be send to the driver):</label>
                      <input type="text" class="form-control" id="message-text">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default confirm-suspend_cancel" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok confirm-suspend">Suspend</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirm-resume" tabindex="-1" role="dialog" aria-labelledby="myModalLabel3" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel3">Confirm Order Payouts Resume</h4>
            </div>
            <div class="modal-body">
                <p>You are about to resume payouts for this order.</p>
                <p>Do you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default confirm-resume_cancel" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok confirm-resume">Resume</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payout-details" tabindex="-1" role="dialog" aria-labelledby="payout-details" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="modal-title" id="payout-details"> Payout Details </h3>
                </div>
                <div class="modal-body">
                    <table class="table" id="payout_details">
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal"> Close </button>
                </div>
            </div>
        </div>
</div>

<footer class="main-footer">
    <div class="pull-right hidden-xs">
    </div>
    <strong>Copyright &copy; 2020 <a href="">{{$site_name}}</a>.</strong> All rights
    reserved.
</footer>