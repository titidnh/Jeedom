<?php
if (!isConnect()) {
    throw new Exception('Error 401 Unauthorized');
}
sendVarToJS('select_id', init('id', '-1'));
sendVarToJS('eqType', 'razberry');
?>

<div class="row">
    <div class="col-lg-2">
        <div class="bs-sidebar affix">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav fixnav">
                <li> 
                    <a class="btn btn-default tooltips" id="bt_syncEqLogic" title="Synchroniser équipement avec le Razberry" style="display: inline-block;"><i class="fa fa-refresh"></i></a>
                    <a class="btn btn-default tooltips changeIncludeState" title="Inclure prériphérique Z-wave" state="1" style="display: inline-block;"><i class="fa fa-sign-in fa-rotate-90"></i></a>
                    <a class="btn btn-default tooltips changeIncludeState" title="Exclure périphérique Z-wave" state="0" style="display: inline-block;"><i class="fa fa-sign-out fa-rotate-90"></i></a>
                </li>
                <li class="nav-header">Liste des équipements Z-wave
                    <i class="fa fa-plus-circle pull-right cursor eqLogicAction" action="add" style="font-size: 1.5em;margin-bottom: 5px;"></i>
                </li>
                <li class="filter" style="margin-bottom: 5px;"><input class="form-control" class="filter form-control" placeholder="Rechercher" style="width: 100%"/></li>
                <?php
                foreach (eqLogic::byType('razberry') as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName() . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <div class="row">
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>Générale</legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Nom de l'équipement</label>
                            <div class="col-lg-8">
                                <input type="text" class="eqLogicAttr form-control" l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" l1key="name" placeholder="Nom de l'équipement"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Node ID</label>
                            <div class="col-lg-8">
                                <input type="text" class="eqLogicAttr form-control" l1key="logicalId" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label" >Objet parent</label>
                            <div class="col-lg-8">
                                <select id="sel_object" class="eqLogicAttr form-control" l1key="object_id">
                                    <option value="">Aucun</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Activer</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr form-control" l1key="isEnable" checked/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Visible</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr form-control" l1key="isVisible" checked/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Délai autorisé entre 2 messages (min)</label>
                            <div class="col-lg-4">
                                <input class="eqLogicAttr form-control" l1key="timeout" checked/>
                            </div>
                        </div>
                    </fieldset> 
                </form>
            </div>
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>Informations</legend>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Batterie</label>
                            <div class="col-lg-5">
                                <span class="razberryInfo tooltips label label-default" l1key="battery"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Derniere communication</label>
                            <div class="col-lg-5">
                                <span class="razberryInfo tooltips label label-default" l1key="lastReceived"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Etat</label>
                            <div class="col-lg-5">
                                <span class="razberryInfo tooltips label label-default" l1key="state"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Marque</label>
                            <div class="col-lg-5">
                                <span class="razberryInfo tooltips label label-default" l1key="brand"></span>
                            </div>
                        </div>



                        <div class="form-group">
                            <label class="col-lg-2 control-label">Classes</label>
                            <div class="col-lg-5">
                                <a class="btn btn-default" id="bt_showClass">Voir/Ajouter commandes préconfigurées</a>
                            </div>
                        </div>
                    </fieldset> 
                </form>
            </div>
        </div>

        <legend>Commandes</legend>
        <a class="btn btn-success btn-sm cmdAction" action="add"><i class="fa fa-plus-circle"></i> Commandes</a><br/><br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">Nom</th>
                    <th style="width: 120px;">Type</th>
                    <th style="width: 100px;">Instance ID</th>
                    <th style="width: 100px;">Class</th>
                    <th style="width: 200px;">Commande</th>
                    <th >Parametres</th>
                    <th style="width: 100px;">Options</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" action="remove"><i class="fa fa-minus-circle"></i> Supprimer</a>
                    <a class="btn btn-success eqLogicAction" action="save"><i class="fa fa-check-circle"></i> Sauvegarder</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>



<div class="modal fade" id="md_addEqLogic">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close" data-dismiss="modal">×</button>
                <h3>Ajouter un équipement Z-wave</h3>
            </div>
            <div class="modal-body">
                <div style="display: none;" id="div_addEqLogicAlert"></div>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Nom de l'équipement Z-wave</label>
                            <div class="col-lg-8">
                                <input class="form-control eqLogicAttr" l1key="name" type="text" placeholder="Nom de l'équipement Z-wave"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Node ID</label>
                            <div class="col-lg-8">
                                <input class="form-control eqLogicAttr" l1key="logicalId" type="text" />
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            <div class="modal-footer">
                <a class="btn btn-danger" data-dismiss="modal"><i class="fa fa-minus-circle"></i> Annuler</a>
                <a class="btn btn-success eqLogicAction" action="newAdd"><i class="fa fa-check-circle icon-white"></i> Enregistrer</a>
            </div>
        </div>
    </div>
</div>


<div id="md_showClass" title="Classes disponible sur l'équipement"></div>

<?php include_file('desktop', 'razberry', 'js', 'razberry'); ?>
<?php include_file('core', 'module.template', 'js'); ?>