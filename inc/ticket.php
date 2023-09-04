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

require '../../../inc/includes.php';

if (!isset($_SESSION['glpiactiveprofile']['id'])) {
    // Session is not valid then exit
    exit;
}

class PluginTransferticketentityTicket extends CommonDBTM
{
    public $checkEntityETT;
    public $checkGroup;
    public $theEntity;
    public $theGroup;
    public $ticketTransferETT;

    public function __construct()
    {
        $this->checkEntityETT = $this->checkEntityETT();
        $this->checkGroup = $this->checkGroup();
        $this->theEntity = $this->theEntity();
        $this->theGroup = $this->theGroup();
        $this->ticketTransferETT = $this->ticketTransferETT();
    }

    /**
     * Ne récupère que les entités qui ont au moins un groupe actif
     *
     * @return $checkEntityETT
     */
    public function checkEntityETT()
    {
        global $DB;
    
        $query = "SELECT E.id
        FROM glpi_groups G
        LEFT JOIN glpi_entities E ON E.id = G.entities_id
        WHERE G.entities_id IS NOT NULL
        AND G.is_assign = 1
        GROUP BY E.id
        ORDER BY E.id";
    
        $result = $DB->query($query);
    
        $checkEntity = array();
    
        foreach ($result as $data) {
            array_push($checkEntity, $data['id']);
        }
    
        return $checkEntity;

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => 'glpi_entities.id',
        //     'FROM' => 'glpi_groups',
        //     'LEFT JOIN' => ['glpi_entities' => ['FKEY' => ['glpi_groups'     => 'entities_id',
        //                                                         'glpi_entities' => 'id']]],
        //     'WHERE' => ['glpi_groups.is_assign' => 1],
        //     'GROUPBY' => 'glpi_entities.id',
        //     'ORDER' => 'glpi_entities.id'
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['id']);
        // }

        // return $array;
    }

    /**
     * Ne récupère que les groupes qui appartiennent à l'entité sélectionnée
     *
     * @return $checkGroup
     */
    public function checkGroup()
    {
        global $DB;
        $entity_choice = $_REQUEST['entity_choice'];
    
        $query = "SELECT G.id
        FROM glpi_groups G
        LEFT JOIN glpi_entities E ON E.id = G.entities_id
        WHERE G.entities_id IS NOT NULL
        AND G.is_assign = 1
        AND E.id = $entity_choice
        ORDER BY E.id";
    
        $result = $DB->query($query);
    
        $checkGroup = array();
    
        foreach ($result as $data) {
            array_push($checkGroup, $data['id']);
        }
    
        return $checkGroup;

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => 'glpi_groups.id',
        //     'FROM' => 'glpi_groups',
        //     'LEFT JOIN' => ['glpi_entities' => ['FKEY' => ['glpi_groups'     => 'entities_id',
        //                                                         'glpi_entities' => 'id']]],
        //     'WHERE' => ['glpi_groups.is_assign' => 1, 'glpi_entities.id' => $entity_choice],
        //     'ORDER' => 'glpi_entities.id'
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['id']);
        // }

        // return $array;
    }

    /**
     * Récupère le nom de l'entité sélectionnée
     *
     * @return $data
     */
    public function theEntity()
    {
        global $DB;
        $entity_choice = $_REQUEST['entity_choice'];

        $query = "SELECT name
        FROM glpi_entities
        WHERE id = $entity_choice";
  
        $result = $DB->query($query);

        foreach ($result as $data) {
            return $data['name'];
        }

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => 'name',
        //     'FROM' => 'glpi_entities',
        //     'WHERE' => ['id' => $entity_choice]
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['name']);
        // }

        // return $array;
    }

    /**
     * Récupère le nom du groupe sélectionné
     *
     * @return $data
     */
    public function theGroup()
    {
        global $DB;
        $group_choice = $_REQUEST['group_choice'];

        $query = "SELECT name
        FROM glpi_groups
        WHERE id = $group_choice";
  
        $result = $DB->query($query);

        foreach ($result as $data) {
            return $data['name'];
        }

        // Test ok
        // $result = $DB->request([
        //     'SELECT' => 'name',
        //     'FROM' => 'glpi_groups',
        //     'WHERE' => ['id' => $group_choice]
        // ]);

        // $array = array();

        // foreach($result as $data){
        //     array_push($array, $data['name']);
        // }

        // return $array;
    }

    /**
     * Effectue les actions nécessaire au transfert d'entité
     * 
     * @return void
     */
    public function ticketTransferETT()
    {
        global $CFG_GLPI;
        global $DB;

        if (isset($_POST['transfertticket'])) {
            $checkEntity = self::checkEntityETT();
            $checkGroup = self::checkGroup();

            $id_ticket = $_POST['id_ticket'];
            $id_user = $_POST['id_user'];
            $theServer = $_POST['theServer'];

            $theEntity = self::theEntity();
            $theGroup = self::theGroup();
            
            $entity_choice = $_REQUEST['entity_choice'];
            $group_choice = $_REQUEST['group_choice'];

            // Vérifie que l'entité sélectionnée appartient à celles disponible
            if (!in_array($entity_choice, $checkEntity)) {
                Session::addMessageAfterRedirect(
                    __(
                        "Veuillez sélectionner une entité valide", 
                        'transferticketentity'
                    ),
                    true,
                    ERROR
                );
    
                header('location:' . $theServer);
            } else if (!in_array($group_choice, $checkGroup)) {
                Session::addMessageAfterRedirect(
                    __(
                        "Veuillez sélectionner un groupe valide", 
                        'transferticketentity'
                    ),
                    true,
                    ERROR
                );
    
                header('location:' . $theServer);
            } else { 
                // Enlève le lien avec l'utilisateur actuel
                $ticket_user = new Ticket_User();
                $ticket_user->delete(
                    [
                    'id'     => $id_ticket,
                    'type' => CommonITILActor::ASSIGN
                    ]
                );

                // Enlève le lien avec le groupe actuel
                $group_ticket = new Group_Ticket();
                $group_ticket->delete(
                    [
                    'id'     => $id_ticket,
                    'type' => CommonITILActor::ASSIGN
                    ]
                );

                // Change le ticket d'entité
                $ticket = new Ticket();
                $ticket->update(
                    [
                    'id'     => $id_ticket,
                    'entities_id' => $entity_choice
                    ]
                );

                // Change le ticket de groupe
                $group_check = [
                    'tickets_id' => $id_ticket,
                    'groups_id' => $group_choice,
                    'type' => CommonITILActor::ASSIGN
                ];
                if (!$group_ticket->find($group_check)) {
                    $group_ticket->add($group_check);
                } else {
                    $group_ticket->update($group_check);
                }

                // Historise le transfert dans une tâche
                $itil_followup = new ITILFollowup();
                $itil_followup->add(
                    [
                    'itemtype' => 'Ticket',
                    'items_id' => $id_ticket,
                    'users_id' => $id_user,
                    'content' => __(
                        "Escalade vers", 
                        "transferticketentity"
                    ) . " $theEntity " .
                    __("dans le groupe", "transferticketentity") . " $theGroup"
                    ]
                );
    
                Session::addMessageAfterRedirect(
                    __(
                        "Transfert réussi pour le ticket n° : ", 
                        "transferticketentity"
                    ) . $id_ticket,
                    true,
                    INFO
                );
    
                $theServer = explode("/ticket.form.php",$theServer);
                $theServer = $theServer[0];
                header('location:' . $theServer . '/central.php');
            }

        } else if (isset($_POST['canceltransfert'])) {
            $id_ticket = $_POST['id_ticket'];
            $theServer = $_POST['theServer'];

            Session::addMessageAfterRedirect(
                __("Transfert annulé", 'transferticketentity'),
                true,
                ERROR
            );

            header('location:' . $theServer);
        }
    }
}

$transferticketentityTicket = new PluginTransferticketentityTicket();