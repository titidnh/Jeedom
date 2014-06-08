<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../core/php/core.inc.php';

class connection {
    /*     * *************************Attributs****************************** */

    private $id;
    private $ip;
    private $failure = 0;
    private $datetime;
    private $username = '';
    private $status = 'ok';
    private $options;

    /*     * ***********************Methode static*************************** */

    public static function all() {
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM `connection`
                ORDER BY `datetime` DESC';
        return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function byId($_id) {
        $value = array(
            'id' => $_id,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM `connection`
                WHERE id=:id';
        return DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function byIp($_ip) {
        $value = array(
            'ip' => $_ip,
        );
        $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
                FROM `connection`
                WHERE ip=:ip';
        return DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }

    public static function failed() {
        $connection = connection::byIp(getClientIp());
        if (!is_object($connection)) {
            $connection = new connection();
            $connection->setIp(getClientIp());
        }
        $connection->setFailure($connection->getFailure() + 1);
        $connection->setStatus('Failed');
        if ($connection->getFailure() > config::byKey('security::retry') &&
                (strtotime($connection->getDatetime()) + 60 * config::byKey('security::backlogtime')) > strtotime(date('Y-m-d H:i:s')) &&
                !self::protectedIp($connection->getIp())) {
            $connection->setStatus('Ban');
        }
        $connection->save();
    }

    public static function protectedIp($_ip) {
        $subnets = explode(config::byKey('security::protectIp'), ',');
        foreach ($subnets as $subnet) {
            if (netMatch($subnet, $_ip)) {
                return true;
            }
        }
        return false;
    }

    public static function success($_username = '') {
        $connection = connection::byIp(getClientIp());
        if (!is_object($connection)) {
            $connection = new connection();
            $connection->setIp(getClientIp());
        }
        $connection->setFailure(0);
        $connection->setUsername($_username);
        $connection->setStatus('Connected');
        $connection->save();
    }

    public static function cron() {
        $sql = 'UPDATE `connection`
                SET `status` = "Not connected"
                WHERE status="Connected"
                    AND `datetime` < DATE_SUB(NOW(),INTERVAL 5 MINUTE)';
        DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
        if (is_numeric(config::byKey('security::bantime'))) {
            $sql = 'UPDATE `connection`
                SET `status` = "Not connected"
                WHERE status="Ban"
                    AND `datetime` < DATE_SUB(NOW(),INTERVAL ' . config::byKey('security::bantime') . ' MINUTE)';
            DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
        }
    }

    /*     * *********************Methode d'instance************************* */

    public function presave() {
        $this->setDatetime(date('Y-m-d H:i:s'));
    }

    public function save() {
        return DB::save($this);
    }

    public function remove() {
        return DB::remove($this);
    }

    /*     * **********************Getteur Setteur*************************** */

    public function getId() {
        return $this->id;
    }

    public function getIp() {
        return $this->ip;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setIp($ip) {
        $this->ip = $ip;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getFailure() {
        return $this->failure;
    }

    public function setFailure($failure) {
        $this->failure = $failure;
    }

    public function getOptions($_key = '', $_default = '') {
        return utils::getJsonAttr($this->options, $_key, $_default);
    }

    public function setOptions($_key, $_value) {
        $this->options = utils::setJsonAttr($this->options, $_key, $_value);
    }

    public function getDatetime() {
        return $this->datetime;
    }

    public function setDatetime($datetime) {
        $this->datetime = $datetime;
    }

}

?>