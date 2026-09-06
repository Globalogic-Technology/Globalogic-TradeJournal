<?php

declare(strict_types=1);
use App\Services\PeriodReviewService;
require_once dirname(__DIR__).'/app/services/PeriodReviewService.php';

function phase12_route(string $path,string $method,array $user):bool
{
    $db=db();$uid=(int)$user['id'];
    if($path==='/pre-post-review' || preg_match('#^/period-review/(\d+)$#',$path,$routeMatch)){
        PeriodReviewService::ensureCurrent($db,$uid);$forcedId=$routeMatch?(int)$routeMatch[1]:0;
        if($method==='POST'){
            verify_csrf();$id=(int)($_POST['review_id']??$forcedId);$goalIds=array_values(array_filter(array_map('intval',(array)($_POST['goal_ids']??[])),fn($v)=>$v>0));$statuses=[];$notes=[];$targets=[];foreach($goalIds as $gid){$statuses[$gid]=(string)($_POST['goal_status'][$gid]??'pending');$notes[$gid]=(string)($_POST['goal_note'][$gid]??'');$targets[$gid]=['value'=>trim((string)($_POST['goal_target'][$gid]['value']??'')),'unit'=>trim((string)($_POST['goal_unit'][$gid]??''))];}
            $responses=[];foreach(['mood','energy','focus','market_condition','higher_timeframe_bias','markets','important_levels','primary_setup','secondary_setup','max_trades','max_daily_loss','max_risk','plan_adherence','risk_adherence','outside_plan','revenge','fomo','moved_stops','grade','best_trade','best_trade_reason','worst_trade','worst_trade_reason','did_well','did_poorly','mistake','change_tomorrow','replay','weekly_objective','monthly_objective','yearly_objective','skills','commitment'] as $f)$responses[$f]=trim((string)($_POST[$f]??''));$responses['stop_conditions']=array_values((array)($_POST['stop_conditions']??[]));PeriodReviewService::saveReview($db,$uid,$id,$responses,$goalIds,$statuses,$notes,$targets);flash('success','Review completed.');redirect('/period-review/'.$id);
        }
        $review=$forcedId?PeriodReviewService::review($db,$uid,$forcedId):null;$reviews=PeriodReviewService::current($db,$uid);if(!$review){$period=in_array((string)($_GET['period']??''),PeriodReviewService::PERIODS,true)?(string)$_GET['period']:'day';$type=in_array((string)($_GET['type']??''),PeriodReviewService::REVIEW_TYPES,true)?(string)$_GET['type']:'pre';foreach($reviews as $r)if($r['period_type']===$period&&$r['review_type']===$type){$review=$r;break;}}
        $goals=PeriodReviewService::goals($db,$uid);$goalRows=$review?PeriodReviewService::goalRows($db,$uid,(int)$review['id']):[];$responses=$review&&$review['responses_json']?json_decode((string)$review['responses_json'],true)?:[]:[];render('pre_post_review',['title'=>'Pre/Post Review','reviews'=>$reviews,'review'=>$review,'goals'=>$goals,'goalRows'=>$goalRows,'responses'=>$responses]);return true;
    }
    if($path==='/review-goals'){
        if($method==='POST'){verify_csrf();$action=(string)($_POST['action']??'save');$gid=(int)($_POST['goal_id']??0);if($action==='delete'){if($gid)$db->prepare('UPDATE review_goals SET is_active=0 WHERE id=? AND user_id=?')->execute([$gid,$uid]);flash('success','Goal removed.');redirect('/review-goals');}$name=trim((string)($_POST['name']??''));$type=(string)($_POST['goal_type']??'custom');PeriodReviewService::createGoal($db,$uid,$name,$type);flash('success','Goal saved.');redirect('/review-goals');}render('review_goals',['title'=>'Review Goals','goals'=>PeriodReviewService::goals($db,$uid)]);return true;
    }
    if($path==='/review-settings'){
        $settings=PeriodReviewService::settings($db,$uid);if($method==='POST'){verify_csrf();$tz=(string)($_POST['timezone']??$settings['timezone']);if(!in_array($tz,DateTimeZone::listIdentifiers(),true))throw new InvalidArgumentException('Invalid review timezone.');$fields=['day_pre_time','day_post_time','week_pre_time','week_post_time','month_pre_time','month_post_time','year_pre_time','year_post_time'];$vals=[];foreach($fields as $f){$v=(string)($_POST[$f]??'');if(!preg_match('/^\d{2}:\d{2}$/',$v))throw new InvalidArgumentException('Review times must use HH:MM.');$vals[$f]=$v.':00';}$ints=['week_pre_day','week_post_day','month_pre_day','year_pre_month','year_pre_day','year_post_month','year_post_day'];foreach($ints as $f)$vals[$f]=max(1,(int)($_POST[$f]??1));$db->prepare('UPDATE review_settings SET timezone=?,day_pre_time=?,day_post_time=?,week_pre_day=?,week_pre_time=?,week_post_day=?,week_post_time=?,month_pre_day=?,month_pre_time=?,month_post_time=?,year_pre_month=?,year_pre_day=?,year_pre_time=?,year_post_month=?,year_post_day=?,year_post_time=? WHERE user_id=?')->execute([$tz,$vals['day_pre_time'],$vals['day_post_time'],$vals['week_pre_day'],$vals['week_pre_time'],$vals['week_post_day'],$vals['week_post_time'],$vals['month_pre_day'],$vals['month_pre_time'],$vals['month_post_time'],$vals['year_pre_month'],$vals['year_pre_day'],$vals['year_pre_time'],$vals['year_post_month'],$vals['year_post_day'],$vals['year_post_time'],$uid]);flash('success','Review schedule updated.');redirect('/review-settings');}render('review_settings',['title'=>'Review Schedule','settings'=>$settings]);return true;
    }
    if($path==='/review-status'){
        $due=PeriodReviewService::due($db,$uid);$preBlock=false;$post=[];foreach($due as $r){if($r['period_type']==='day'&&$r['review_type']==='pre')$preBlock=true;if($r['review_type']==='post')$post[]=$r;}header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'pre_blocked'=>$preBlock,'due'=>$due,'post_due'=>$post,'notification_unread'=>\App\Services\NotificationService::unread($db,$uid),'review_queue_count'=>count($due)],JSON_UNESCAPED_SLASHES);return true;
    }
    return false;
}
