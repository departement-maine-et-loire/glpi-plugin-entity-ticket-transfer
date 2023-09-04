<?php
/**
 -------------------------------------------------------------------------
 LICENSE

 This file is part of Transferticketentity plugin for GLPI.

 Transferticketentity is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Transferticketentity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @category  Ticket
 @package   Transferticketentity
 @author    Yannick Comba <y.comba@maine-et-loire.fr>
 @copyright 2015-2023 Département de Maine et Loire plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            https://www.gnu.org/licenses/gpl-3.0.html
 @link      https://github.com/departement-maine-et-loire/
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginTransferticketentityTicket extends Ticket
{
    /**
     * Vérifie le profil de l'utilisateur
     *
     * @return $checkProfiles
     */
    public function checkProfiles()
    {
        global $DB;

        $query = "SELECT id_profiles
        FROM glpi_plugin_transferticketentity_profiles";

        $result = $DB->query($query);

        $checkProfiles = array();

        foreach ($result as $data) {
            array_push($checkProfiles, $data['id_profiles']);
        }

        return $checkProfiles;

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => 'id_profiles',
        //     'FROM' => 'glpi_plugin_transferticketentity_profiles'
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['id_profiles']);
        // }

        // return $array;
    }

    /**
     * Si le profil est autorisé, ajoute un onglet supplémentaire
     *
     * @param string $item         Ticket
     * @param int    $withtemplate 0
     * 
     * @return "Entity ticket transfer"
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $checkProfiles = self::checkProfiles();

        if (in_array($_SESSION['glpiactiveprofile']['id'], $checkProfiles)) {
            if ($item->getType() == 'Ticket') {
                return __("Transfert d'entité", "transferticketentity");
            }
            return '';
        }
    }

    /**
     * Donne l'entité du ticket
     *
     * @return $data
     */
    public function getTicketEntity()
    {
        global $DB;

        $id_ticket = $_SERVER["QUERY_STRING"];
        $id_ticket = preg_replace('/[^0-9]/', '', $id_ticket);
        $id_ticket = substr($id_ticket, 1);
        
        $query = "SELECT E.id, E.name
        FROM glpi_tickets T
        LEFT JOIN glpi_entities E ON E.id = T.entities_id
        WHERE T.id = $id_ticket";

        $result = $DB->query($query);

        foreach ($result as $data) {
            return [$data['id'], $data['name']];
        }

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => ['glpi_entities.id', 'glpi_entities.name'],
        //     'FROM' => 'glpi_tickets',
        //     'LEFT JOIN' => ['glpi_entities' => ['FKEY' => ['glpi_tickets'     => 'entities_id',
        //                                                    'glpi_entities' => 'id']]],
        //     'WHERE' => ['glpi_tickets.id' => $id_ticket]
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['id'], $data['name']);
        // }

        // return $array;
    }

    /**
     * Vérifie que le ticket n'est pas clos
     *
     * @return $data
     */
    public function checkTicket()
    {
        global $DB;

        $id_ticket = $_SERVER["QUERY_STRING"];
        $id_ticket = preg_replace('/[^0-9]/', '', $id_ticket);
        $id_ticket = substr($id_ticket, 1);

        $query = "SELECT id
        FROM glpi_tickets
        WHERE `status` = 6";
        
        $result = $DB->query($query);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data['id']);
        }

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => 'id',
        //     'FROM' => 'glpi_tickets',
        //     'WHERE' => ['status' => 6]
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['id']);
        // }

        if(!in_array($id_ticket, $array)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Permet de récuper le groupe assigné au ticket
     *
     * @return $data
     */
    public function getTicketGroup()
    {
        global $DB;

        $id_ticket = $_SERVER["QUERY_STRING"];
        $id_ticket = preg_replace('/[^0-9]/', '', $id_ticket);
        $id_ticket = substr($id_ticket, 1);

        $query = "SELECT *
        FROM glpi_groups_tickets
        WHERE tickets_id = $id_ticket
        AND TYPE = 2";

        $result = $DB->query($query);

        $array = array();

        foreach ($result as $data) {
            array_push($array, $data['groups_id']);
        }

        return $array;

        // Test ok
        // $result = $DB->request([
        //     'FROM' => 'glpi_groups_tickets',
        //     'WHERE' => ['tickets_id' => $id_ticket, 'type' => 2]
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['groups_id']);
        // }

        // return $array;
    }

    /**
     * Permet de récupérer toutes les entités qui ont au moins un groupe ET utilisées
     *
     * @return $allEntities
     */
    public function getAllEntities()
    {
        global $DB;
        $getTicketEntity = self::getTicketEntity();
        $theEntity = $getTicketEntity[0];

        $query = "SELECT E.id, E.name
        FROM glpi_groups G
        LEFT JOIN glpi_entities E ON E.id = G.entities_id
        LEFT JOIN glpi_tickets T ON T.entities_id = E.id
        WHERE G.entities_id IS NOT NULL
        AND G.is_assign = 1
        AND E.id != $theEntity
        GROUP BY E.id
        ORDER BY E.id";

        $result = $DB->query($query);

        $allEntities = array();

        foreach ($result as $data) {
            array_push($allEntities, $data['id'], $data['name']);
        }

        return $allEntities;

        // Test NOK
        // $result = $DB->request([
        //     'SELECT' => ['glpi_entities.id', 'glpi_entities.name'],
        //     'FROM' => 'glpi_groups',
        //     'LEFT JOIN' => ['glpi_entities' => ['FKEY' => ['glpi_groups'     => 'entities_id',
        //                                                    'glpi_entities' => 'id']]],
        //     'LEFT JOIN' => ['glpi_tickets' => ['FKEY' => ['glpi_tickets'     => 'entities_id',
        //                                                   'glpi_entities' => 'id']]],
        //     'WHERE' => ['glpi_groups.entities_id' => 'NOT NULL', 'glpi_groups.is_assign' => 1, 'glpi_entities.id' != $theEntity],
        //     'GROUPBY' => 'glpi_entities.id',
        //     'ORDER' => 'glpi_entities.id'
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['id'], $data['name']);
        // }

        // return $array;
    }

    /**
     * Permet de récupérer les groupes auxquels des tickets peuvent être assignés
     *
     * @return $allGroupsEntities
     */
    public function getGroupEntities()
    {
        global $DB;

        $query = "SELECT *
        FROM glpi_groups
        WHERE is_assign = 1
        ORDER BY entities_id ASC, id ASC";

        $result = $DB->query($query);

        $allGroupsEntities = array();

        foreach ($result as $data) {
            array_push(
                $allGroupsEntities, $data['id'], 
                $data['entities_id'], $data['name']
            );
        }

        return $allGroupsEntities;

        // Test ok
        // $result = $DB->request([
        //     'FROM' => 'glpi_groups',
        //     'WHERE' => ['is_assign' => 1],
        //     'ORDER' => ['entities_id', 'id']
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push(
        //         $array, $data['id'], 
        //         $data['entities_id'], $data['name']
        //     );
        // }

        // return $array;
    }

    /**
     * Si on est sur les tickets, affiche un onglet supplémentaire
     * 
     * @param string $item         Ticket
     * @param int    $tabnum       1
     * @param int    $withtemplate 0
     * 
     * @return true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Ticket') {
            $ID   = $item->getID();
            $profile = new self();
            if (!isset($_SESSION['glpi_plugin_transferticketentity_profile']['id'])) {
                PluginTransferticketentityProfileRights::changeProfile();
            }
            $profile->showFormMcv($ID);
        }

        return true;
    }

    /**
     * Affiche le form pour effectuer le transfert de ticket
     *
     * @return void
     */
    public function showFormMcv()
    {
        global $CFG_GLPI;
        global $DB;

        // $test = self::getGroupEntities();
        // var_dump($test);

        $entity_choice = 1;

        // Devrait être ok
        $result = $DB->request([
            'SELECT' => 'glpi_groups.id',
            'FROM' => 'glpi_groups',
            'LEFT JOIN' => ['glpi_entities' => ['FKEY' => ['glpi_groups'     => 'entities_id',
                                                                'glpi_entities' => 'id']]],
            'WHERE' => ['glpi_groups.is_assign' => 1, 'glpi_entities.id' => $entity_choice],
            'ORDER' => 'glpi_entities.id'
        ]);

        $array = array();

        foreach($result as $data){
            array_push($array, $data['id']);
        }

        $array = array();

        foreach($result as $data){
            array_push($array, $data['id']);
        }

        var_dump($array);

        $getAllEntities = self::getAllEntities();
        $getGroupEntities = self::getGroupEntities();

        $theServer = explode("front/profile.form.php?",$_SERVER["HTTP_REFERER"]);
        $theServer = $theServer[0];

        $id_ticket = $_SERVER["QUERY_STRING"];
        $id_ticket = preg_replace('/[^0-9]/', '', $id_ticket);
        $id_ticket = substr($id_ticket, 1);

        $id_user = $_SESSION["glpiID"];
        $checkTicket = self::checkTicket();

        if($checkTicket == false) {
            echo "<div style='text-align: center;'>";
                echo "<p style='color: red; font-size: 1.25rem; padding-top: 2rem;'>".
                    __("Transfert non autorisé sur ticket clos.", "transferticketentity")
                    ."</p>";
            echo "</div>";

            return false;
        }

        echo "<style>         
                .tt_modal {
                    background-color: #fefefe;
                    margin: auto;
                    max-width: 600px;
                    padding: 15px;
                    border: 1px solid #888;
                    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
                    text-align: center;
                    z-index: 10001;
                }
        </style>";

        echo "<div id='tt_gest_error'>";
            echo "<p style='color:red;'>".__("Erreur, veuillez recharger la page.", "transferticketentity")."</p>";
            echo "<p style='color:red;'>".__("Si le problème persiste, vous pouvez tenter de vider le cache en faisant CTRL + F5.", "transferticketentity")."</p>";
        echo "</div>";

        echo"
        <form class='form_transfert' style='margin:auto; display:none' action='../plugins/transferticketentity/inc/ticket.php' method='post'>
            <div class='tt_entity_choice'>
                <label for='entity_choice'>".__("Sélectionnez l'entité vers laquelle migrer le ticket", "transferticketentity")." : </label>
                <select name='entity_choice' id='entity_choice'>
                    <option selected disabled value=''>-- ".__("Choisissez votre entité", "transferticketentity")." --</option>";
        for ($i = 0; $i < count($getAllEntities); $i = $i+2) {
            echo "<option value='" . $getAllEntities[$i] . "'>" . $getAllEntities[$i+1] . "</option>";
        }
                echo "</select>
            </div>

            <div style='display:flex;'>
                <div class='tt_group_choice' style='display: none;'>
                    <label for='group_choice'>".__("Sélectionnez le groupe à assigner", "transferticketentity")." : </label>
                    <select name='group_choice' id='group_choice'>
                        <option id='no_select' disabled value=''>-- ".__("Choisissez votre groupe", "transferticketentity")." --</option>";
        for ($i = 0; $i < count($getGroupEntities); $i = $i+3) {
            echo "<option class='tt_plugin_entity_" . $getGroupEntities[$i+1] . "' value='" . $getGroupEntities[$i] . "'>" . $getGroupEntities[$i+2] . "</option>";
        }
                    echo "</select>
                </div>

                <div style='display:none'>
                    <input type ='number' id='id_ticket' value= '$id_ticket' name='id_ticket' style='display: none;' readonly>
                    <input type ='number' id='id_user' value= '$id_user' name='id_user' style='display: none;' readonly>
                    <input type ='text' id='theServer' value= '$theServer' name='theServer' style='display: none;' readonly>
                </div>

                <div id='div_confirmation' style='display: none; padding-left: .5rem;'>
                    <button id='tt_btn_open_modal_form' style='display:inline-flex;align-items: center;justify-content: center;white-space: nowrap;border: 1px solid rgba(98, 105, 118, 0.24);border-radius: 4px;font-weight: 500;line-height: 1.4285714286;padding: 0.4375rem 1rem;'>".__("Valider", "transferticketentity")."</button>
                </div>
            </div>

            <dialog id='tt_modal_form_adder' class='tt_modal'>
                <h2 style='color:black; font-weight:normal;'>".__("Confirmer le transfert ?", "transferticketentity")."</h2>
                <p style='color:black; font-weight:normal; padding-bottom:3rem;'>".__("Une fois le transfert effectué, le ticket restera visible uniquement si vous avez les droits requis.", "transferticketentity")."</p>
                <button type='submit' name='canceltransfert' id='canceltransfert' style='display:inline-flex;align-items: center;justify-content: center;white-space: nowrap;background-color: #f00020;color: white;border: 1px solid rgba(98, 105, 118, 0.24);border-radius: 4px;font-weight: 500;line-height: 1.4285714286;padding: 0.4375rem 2rem; margin-right:1rem;'>".__("Annuler", "transferticketentity")."</button>
                <button type='submit' name='transfertticket' id='transfertticket' style='display:inline-flex;align-items: center;justify-content: center;white-space: nowrap;background-color: #80cead;color: #1e293b;border: 1px solid rgba(98, 105, 118, 0.24);border-radius: 4px;font-weight: 500;line-height: 1.4285714286;padding: 0.4375rem 2rem;'>".__("Confirmer", "transferticketentity")."</button>
            </dialog>";
        Html::closeForm();

        echo "<script>       
            if(document.querySelector('.tt_entity_choice') != null) {
                document.querySelector('#tt_gest_error').style.display='none'
                document.querySelector('.form_transfert').style.display=''
            
                let entity_choice = document.querySelector('#entity_choice')
                let tt_group_choice = document.querySelector('.tt_group_choice')
                let tt_btn_open_modal_form = document.querySelector('#tt_btn_open_modal_form')
            
                const clone_all_groups = document.querySelectorAll('#group_choice option')
                let all_groups = []
            
                let all_groups_unchoice = document.querySelectorAll('#group_choice option')
                all_groups_unchoice.forEach(function(all_group_unchoice) {
                    all_group_unchoice.remove()
                })
            
                entity_choice.addEventListener('click', function (event) {
                    if(entity_choice.value == '') {
                        tt_group_choice.style.display = 'none'
                        tt_btn_open_modal_form.disabled = true
                        tt_btn_open_modal_form.style.backgroundColor = '#D3D3D3'
                        tt_btn_open_modal_form.style.color = '#FFFFFF'
                        tt_btn_open_modal_form.style.cursor = 'not-allowed'
                    } else {
                        tt_group_choice.style.display = ''
                        document.querySelector('#div_confirmation').style.display = ''
                        document.querySelector('.tt_group_choice').style.display = ''
                    }
                })
            
                entity_choice.addEventListener('change', function (event) {
                    all_groups = []
                    all_groups = clone_all_groups
            
                    all_groups.forEach(function(all_group) {
                        if('tt_plugin_entity_' + entity_choice.value == all_group.className || all_group.value == '') {
                            document.querySelector('#group_choice').appendChild(all_group)
                        } else {
                            all_group.remove()
                        }
                    })
            
                    document.querySelector('#no_select').selected = true
                })
            
                document.querySelector('.form_transfert').addEventListener('click', function (event) {
                    if(document.querySelector('#group_choice').value == '') {
                        tt_btn_open_modal_form.disabled = true
                        tt_btn_open_modal_form.style.backgroundColor = '#D3D3D3'
                        tt_btn_open_modal_form.style.color = '#FFFFFF'
                        tt_btn_open_modal_form.style.cursor = 'not-allowed'
                    } else {
                        tt_btn_open_modal_form.disabled = false
                        tt_btn_open_modal_form.style.backgroundColor = '#80cead'
                        tt_btn_open_modal_form.style.color = '#1e293b'
                        tt_btn_open_modal_form.style.cursor = 'pointer'
                    }
                })
            
                let modal_form_adder = document.getElementById('tt_modal_form_adder')
            
                document.querySelector('#canceltransfert').addEventListener('click', function(event){
                    event.preventDefault()
                    modal_form_adder.close();
                });
            
                tt_btn_open_modal_form.addEventListener('click', function(event){
                    event.preventDefault()
                    modal_form_adder.showModal();
                });
            }
        </script>";
    }
}