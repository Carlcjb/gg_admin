<?php
/**
 * Created by PhpStorm.
 * User: Cherish
 * Date: 2016/12/23
 * Time: 7:47
 */
namespace Admin\Controller;
use Think\Controller\RestController;

class MonitorController extends RestController {

    /**
     * 建立索引
     */
    public function buildIndexGet() {
        //校验KEY
        $key = C('APP_KEY');
        if(I('get.key') != md5($key)) {
            echo "app_key is wrong\n";
            return;
        }
        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db_name = C('MONGO_DB');
        $db = $mongo_client->$db_name;

        //admin_user
        $c = new \MongoCollection($db, 'admin_user');
        $c->deleteIndexes();
        $c->createIndex(array('username' => 1), array());
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_agent
        $c = new \MongoCollection($db, 'admin_agent');
        $c->deleteIndexes();
        $c->createIndex(array('username' => 1), array());
        $c->createIndex(array('cellphone' => 1), array());
        $c->createIndex(array('wechat' => 1), array());
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_card
        $c = new \MongoCollection($db, 'admin_card');
        $c->deleteIndexes();
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_mail
        $c = new \MongoCollection($db, 'admin_mail');
        $c->deleteIndexes();
        $c->createIndex(array('title' => 1), array());
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_user
        $c = new \MongoCollection($db, 'admin_user');
        $c->deleteIndexes();
        $c->createIndex(array('username' => 1), array());
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_menu
        $c = new \MongoCollection($db, 'admin_menu');
        $c->deleteIndexes();
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('action' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_notice
        $c = new \MongoCollection($db, 'admin_notice');
        $c->deleteIndexes();
        $c->createIndex(array('title' => 1), array());
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_popup
        $c = new \MongoCollection($db, 'admin_popup');
        $c->deleteIndexes();
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_role
        $c = new \MongoCollection($db, 'admin_role');
        $c->deleteIndexes();
        $c->createIndex(array('name' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_stock
        $c = new \MongoCollection($db, 'admin_stock');
        $c->deleteIndexes();
        $c->createIndex(array('remark' => 1), array());
        $c->createIndex(array('apply_user' => 1), array());
        $c->createIndex(array('audit_user' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_stock_grant_record
        $c = new \MongoCollection($db, 'admin_stock_grant_record');
        $c->deleteIndexes();
        $c->createIndex(array('from_user' => 1), array());
        $c->createIndex(array('to_user' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //admin_trotting
        $c = new \MongoCollection($db, 'admin_trotting');
        $c->deleteIndexes();
        $c->createIndex(array('admin' => 1), array());
        $c->createIndex(array('date' => 1), array());

        //agent_stock_grant_record
        $c = new \MongoCollection($db, 'agent_stock_grant_record');
        $c->deleteIndexes();
        $c->createIndex(array('nickname' => 1), array());
        $c->createIndex(array('from_user' => 1), array());
        $c->createIndex(array('to_user' => 1), array());
        $c->createIndex(array('date' => 1), array());

        echo "create mongodb index at " . date("Y-m-d H:i:s", time()) . "\n";
    }


    /**
     * @desc 游戏日，月消耗
     */
    public function streamGet() {
        //校验KEY
        $key = C('APP_KEY');
        if(I('get.key') != md5($key)) {
            echo "app_key is wrong\n";
            return;
        }

        // day 日
        // month 月
        $type = I('get.type', 'day');
        switch ($type) {
            case "day":
                $end_date = strtotime(date("Y-m-d 00:00:00", time()));
                $start_date = $end_date - 86400;
                break;
            case "month":
                $end_date = strtotime(date("Y-m-t", (strtotime("-1 month"))));
                $start_date = strtotime(date("Y-m-01", (strtotime("-1 month"))));
                break;
        }

        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db_name = C('MONGO_DB');
        $db = $mongo_client->$db_name;
        $ticket_use_record = $db->ticket_use_record;

        $cursor = $ticket_use_record->find(array('usedTime' => array('$gte' => $start_date, '$lt' => $end_date)));
        $count = 0;
        foreach($cursor as $item) {
            $count += $item['usedCnt'];
        }
        //写入admin_report_stream_retail 零售表
        $retail_data['date'] = $start_date;
        $retail_data['game'] = 1;
        $retail_data['buy_card'] = 0; //TODO
        $retail_data['stream'] = $count;

        $admin_report_stream_retail = $db->admin_report_stream_retail;
        $admin_report_stream_retail->update(array("date"=>$start_date), array('$set' => $retail_data), array('upsert' => true));
        echo "零售统计完成\n";

        //统计代理流水
        $agent_stock_grant_record = $db->agent_stock_grant_record;
        $cursor = $agent_stock_grant_record->find(array('date' => array('$gte' => $start_date, '$lt' => $end_date)));
        $count = 0;
        foreach ($cursor as $item) {
            $count += $item['amount'];//给玩家充卡，消耗量
        }
        $agent_data['date'] = $start_date;
        $agent_data['game'] = 1;
        $agent_type = C('SYSTEM.AGENT_TYPE');
        foreach ($agent_type as $key => $value) {
            $agent_data['buy_card'][$key] = 0; //TODO
        }
        $agent_data['expense'] = $count;
        $agent_data['stream'] = $count;
        $admin_report_stream_day = $db->admin_report_stream_day;
        $admin_report_stream_day->update(array("date" => $start_date), array('$set' => $agent_data), array('upsert' => true));
        echo "统计代理完成\n";
    }

    /**
     * @desc 代理月流水
     */
    public function agentStreamGet() {
        //校验KEY
        $key = C('APP_KEY');
        if(I('get.key') != md5($key)) {
            echo "app_key is wrong\n";
            return;
        }

        // day 日
        // month 月
        $type = I('get.type', 'day');
        switch ($type) {
            case "day":
                $end_date = strtotime(date("Y-m-d 00:00:00", time()));
                $start_date = $end_date - 86400;
                break;
            case "month":
                $end_date = strtotime(date("Y-m-t", (strtotime("-1 month"))));
                $start_date = strtotime(date("Y-m-01", (strtotime("-1 month"))));
                break;
        }

        $mongo_client = new \MongoClient(C('MONGO_SERVER'));
        $db_name = C('MONGO_DB');
        $db = $mongo_client->$db_name;

        //agent_stock_grant_record
        $agent_stock_grant_record = $db->agent_stock_grant_record;
        $cursor = $agent_stock_grant_record->group(
            array('from_user' => 1),
            array('count' => 0),
            "function (obj, prev) {
                 prev.count += obj.amount;
             }",
            array(
                'condition' => array(
                    'date' => array(
                        '$gte' => $start_date,
                        '$lt' => $end_date,
                    )
                )
            )
        );
        $admin_agent = $db->admin_agent;
        $admin_report_agent_stream_month = $db->admin_report_agent_stream_month;
        foreach($cursor['retval'] as $item) {
            //根据用户名查找用户信息
            $agent = $admin_agent->findOne(array("username" => $item['from_user']));
            if ($agent) {
                $data = array(
                    'date' => $start_date,
                    'game' => 1,
                    'username' => $agent['username'],
                    'name' => $agent['name'] ? $agent['name'] : "",
                    'wechat' => $agent['wechat'] ? $agent['wechat'] : "",
                    'type' => $agent['type'],
                    'pay_back' => 0, //TODO
                    'purchase' => 0, //TODO
                    'expense' => intval($item['count']),
                );
                $admin_report_agent_stream_month->update(array('date'=>$start_date,
                    'username' => $item['from_user']), array('$set' => $data), array('upsert' => true));
            }
        }
        echo "代理月报表执行完成\n";
    }

}