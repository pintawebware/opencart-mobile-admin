<?php echo $header; ?>
<div id="content">
    <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </ul>
    <div class="page-header">
        <div class="container-fluid">
            <?php if($version && empty($ext)){ ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Обновите модуль
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php } ?>
            <?php if ($error_warning) { ?>
            <div class="warning"><?php echo $error_warning; ?></div>
            <?php } ?>
            <div class="pull-right" style="float: right;">
                <?php if($version){ ?>

                <a href="<?php echo $update; ?>" data-toggle="tooltip" title="<?php echo $button_update; ?>"
                   class="btn btn-success"><i class="fa fa-refresh"></i></a>

                <?php } ?>
                <div class="buttons">
                    <a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a>
                    <a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
            </div>
            <h1><?php echo $heading_title; ?></h1>

        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">

<div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store; ?></label>
                <div class="col-sm-10">
                    <div class="well well-sm" style="height: 150px; overflow: auto;">
                        <div class="checkbox">
                            <label>
                                <?php if (in_array(0, $apimodule_store)) { ?>
                                <input type="checkbox" name="apimodule_store[]" value="0" checked="checked" />
                                <?php echo $text_default; ?>
                                <?php } else { ?>
                                <input type="checkbox" name="apimodule_store[]" value="0" />
                                <?php echo $text_default; ?>
                                <?php } ?>
                            </label>
                        </div>
                        <?php foreach ($stores as $store) { ?>
                        <div class="checkbox">
                            <label>
                                <?php if (in_array($store['store_id'], $apimodule_store)) { ?>
                                <input type="checkbox" name="apimodule_store[]" value="<?php echo $store['store_id']; ?>" checked="checked" />
                                <?php echo $store['name']; ?>
                                <?php } else { ?>
                                <input type="checkbox" name="apimodule_store[]" value="<?php echo $store['store_id']; ?>" />
                                <?php echo $store['name']; ?>
                                <?php } ?>
                            </label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="apimodule_status" id="input-status" class="form-control">
                                <?php if ($apimodule_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>