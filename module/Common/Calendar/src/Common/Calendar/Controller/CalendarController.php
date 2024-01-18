<?php

namespace Common\Calendar\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;

class CalendarController extends AbstractActionController
{
    protected $_calendarMapper;
    protected $_adminMapper;
    public $userActivities;
    public $genericActivites;
    public $output;
    public $hasData;
    public $tArray; // contains all holiday periods for each region
    public $allDays;
    public $regionMeta;
    public $today;
    public $requYMD;
    public $curMonthTS;
    public $monthNr;
    public $num_of_days;
    public $sReportsData;
    public $sUsersData;
    public $sReportDesc;
    public $sUserNames;
    public $iColSpan;
    public $iReportCount;
    public $iUsersCount;
    public $iAccessFormId;
    public $iFormId;
    protected  $_auditService;

    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Common\Audit\Service');
        }
        return $this->_auditService;
    }
    
    public function getCalendarService() 
    {
        if (!$this->_calendarMapper) {
            $sm = $this->getServiceLocator();
            $this->_calendarMapper = $sm->get('Common\Calendar\Model\Calendar'); 
        }
        return $this->_calendarMapper;
    }
    
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id']) ? $userDetails['group_id'] : 0;
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return false;
        }
        return true;
    }

    /*
     * function to view Calendar for tracker
     */
    public function viewAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        
        $this->today = date('Y-m-d');
        $this->requYMD = date('Y-m');
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('form_id', 0); 

        if (!is_numeric($trackerId) || $trackerId == 0 || !is_numeric($formId) || $formId == 0) {
            $this->redirect()->toRoute('tracker');
        }
        
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        }
        $trackerDetails = $this->getCalendarService()->trackerResults($trackerId);
        $legend = $this->getLegend($trackerId);
        $this->iReportCount = 0;
        $this->iUsersCount = 0;
        $arrFormIds = isset($trackerDetails['forms'])?array_column($trackerDetails['forms'], 'form_id'):array();
        $arrFormNames = isset($trackerDetails['forms'])?array_column($trackerDetails['forms'], 'form_name', 'form_id'):array();
        $formName = isset($arrFormNames[$formId]) ? $arrFormNames[$formId] : '';
        if (count($arrFormIds) > 0) {
            if (in_array($formId, $arrFormIds)) {
                $this->iAccessFormId=$formId;
            } else {
                $this->iAccessFormId=isset($trackerDetails['forms'][0]['form_id'])?$trackerDetails['forms'][0]['form_id']:0;
            }
        } else {
            $this->iAccessFormId=0;
        }
        
        $this->iFormId = $formId;
        $calendar = $this->getAllEvents($trackerId, $formId, $this->requYMD);
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        return array(
            'trackerId' => $trackerId,
            'formId' => $formId,
            'trackerResults' => $trackerDetails,
            'calendar' => $calendar,
            'requYMD' => $this->requYMD,
            'legend' => $legend, 
            'reportCount' => $this->iReportCount,
            'usersCount' => $this->iUsersCount,
            'formName'   => $formName
        );
    }

    public function getDates($month) 
    {
        /* DATE PREPARATIONS */
        // http://php.net/manual/en/function.date.php
        // makes it first of month
        $startpage = true;
        if (isset($month)) {
            $this->requYMD = preg_replace("/[^0-9\-]/i", '', $month) . '-01';
            $startpage = false;
        }
        // block hack, required yyyy-mm-dd
        if (strlen($this->requYMD) != 10) {
            exit();
        }
        
        // get current month
        $this->curMonthTS = strtotime($this->requYMD); // add 4 hours 
        $this->monthNr = date('n', $this->curMonthTS); // numeric representation of current month, without leading zeros
        // echo strftime('%s %H:%M:%S %z %Z %a., %d. %B %Y', $this->curMonthTS);     
        // number of days in the given month
        $this->num_of_days = date('t', $this->curMonthTS);
        $x_year = date('Y', $this->curMonthTS);
        $x_month = date('m', $this->curMonthTS);
        for ($i = 1; $i <= $this->num_of_days; $i++) {
            $dates[] = $x_year . "-" . $x_month . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        return $dates;
    }
    public function getMonthDates($month)
    {
        if (empty($month)) {
            $month = date('Y-m-d');
        }        
        $dates = $this->getDates($month);
        
        $monthData = '<tr class="text-center align-middle">';
        $inc = 0;
        $now = date('Ymd');
        foreach ($dates as $day) {
            $compareDate = date('Ymd', strtotime($day));
            $weekendCell = '';
            $weekdayName = strftime('%a', strtotime($day));
            if ($now == $compareDate) {
                $weekendCell = 'todayHeaderCell ';
            }
            if ($weekdayName == 'Sat' || $weekdayName == 'Sun') {
                $weekendCell .= 'weekendCell';
            }
            $weekendCell = trim($weekendCell);
            $monthData .= '<th class="' . $weekendCell . '" title="' . strftime('%A %e. %B %Y', strtotime($day)) . '">' . ltrim(substr($day, 8, 2), '0') . '<br />' . $weekdayName . '</th>'; // alternative: output $day and let JS convert the day to weekday
            $inc++;
        }
        $monthData .= '</tr>';
        $this->iColSpan = $inc;
        
        return $monthData;
    }
    
    public function getAllEvents($trackerId, $formId, $month) 
    {
        if (empty($month)) {
            $month = date('Y-m-d');
        }
        $dateFormat = $this->getCalendarService()->getCondition($formId);
        $dates = $this->getDates($month);
        $this->userActivities = $this->getCalendarService()->getUserActivityList($trackerId, $this->requYMD);
        $this->genericActivites = $this->getCalendarService()->getGeneriActivityList($trackerId, $this->requYMD);
        $userNames = $this->getCalendarService()->getUsernames($trackerId);
        $minHeight = 0;
        if ($this->iAccessFormId > 0 && $this->iAccessFormId == $this->iFormId) {
            $list = $this->getProductReportDates($trackerId, $formId);
            if (!isset($list['list'])) {
                $this->setEmptyProductReportEvents();
            } else {
                $minHeight = 80;
                $this->setProductReportEvents($trackerId, $formId, $month, $dateFormat);                
            }            
        } else {
            $this->setEmptyProductReportEvents();
        }
        $this->setUserEvents($trackerId, $month, $dateFormat);
        
        $this->output = '<div class="row m-0 p-0">'
                . '<div class="col-8 col-sm-8 col-md-6 col-lg-3 m-0 p-0">'
                . '<table class="calenderTable table table-sm m-0 p-0">'
                . $this->showMonthsBar($this->requYMD)
                /*. '<tr>'
                . '<td class="calendarSection bg-light border-light border-right align-middle p-1">'
                . '<i class="fas fa-book ml-2"></i> Reports'
                . '</td>'
                . '</tr>'*/
                . '<tr class="type-split">'
                . '<td class="m-0 p-0">'
                . $this->sReportsDesc
                . '</td>'
                . '</tr>'
                /*. '<tr>'
                . '<td class="calendarSection bg-light border-light border-right align-middle p-1">'
                . '<i class="fas fa-users ml-2"></i> Users'
                . '</td>'
                . '</tr>'*/
                . '<tr>'
                . '<td class="m-0 p-0">'
                . $this->sUserNames
                . '</td>'
                . '</tr>'
                . '</table>'
                . '</div>'
                . '<div class="col-4 col-sm-4 col-md-6 col-lg-9 m-0 p-0">'
                . '<div class="rounded-right table-responsive">'
                . '<table class="calenderTable table table-sm calenderHeader m-0 p-0">'
                . '<thead class="thead-light">'
                . $this->getMonthDates($month)
                . '</thead>'
                . '<tbody>'
                /*. '<tr>'
                . '<td class="calendarSection bg-light align-middle" colspan="' . $this->iColSpan .'">'
                . '</td>'
                . '</tr>'*/
                . '<tr class="type-split">'
                . '<td class="m-0 p-0" colspan="' . $this->iColSpan .'">'
                . '<div class="table-responsive card-body m-0 p-0" id="tblReportsDetailContainer" style="display: inline-block; min-height: '.$minHeight.'px; max-height: 150px; overflow: hidden">'
                . '<table id="tblReportsDetail" class="calenderTable table table-sm table-borderless table-striped table-hover calenderBody m-0 p-0">'
                . '<tbody>'
                . $this->sReportsData
                . '</tbody>'
                . '</table>'
                . '</div>'
                . '</td>'
                . '</tr>'
                /*. '<tr>'
                . '<td class="calendarSection bg-light align-middle" colspan="' . $this->iColSpan .'">'
                . '</td>'
                . '</tr>'*/
                .  '<tr>'
                . '<td class="m-0 p-0" colspan="' . $this->iColSpan .'">'
                . '<div class="table-responsive card-body m-0 p-0" id="tblUsersDetailContainer" style="display: inline-block; overflow: hidden">'
                . '<table id="tblUsersDetail" class="calenderTable table table-sm table-borderless table-striped table-hover calenderBody m-0 p-0">'
                . '<tbody>'
                . $this->sUsersData
                . '</tbody>'
                . '</table>'
                . '</div>'
                . '</td>'
                . '</tr>'
                . '</tbody>'
                . '</table>'
                . '</div>'
                . '</div>'
                . '</div>';
        
        return $this->output;
    }
    public function setEmptyProductReportEvents()
    {
        //   $dates = $this->getDates($month);
        // $now = date('Ymd');
        $this->sReportsDesc = '<div class="table-responsive card-body m-0 p-0" id="tblReportsCategoriesContainer" '
                . 'style="display: inline-block; min-height: 0px; max-height: 150px; overflow-y: scroll;" '
                . 'onscroll="syncScrollbars(\'#tblReportsCategoriesContainer\',\'#tblReportsDetailContainer\')">'
                . '<table id="tblReportsCategories" class="calenderTable table table-sm table-striped table-hover m-0 p-0 calenderCategories">'
                . '<tbody>';
        $this->sReportsDesc .= '</tbody>'
                . '</table>'
                . '</div>';                
    }
    public function setUserEvents($trackerId, $month, $dateFormat)
    {
        $dates = $this->getDates($month);
        $this->userActivities = $this->getCalendarService()->getUserActivityList($trackerId, $this->requYMD);
        $userNames = $this->getCalendarService()->getUsernames($trackerId);
        $this->iUsersCount = count($userNames) > 0 ? count($userNames) : 0;
        $wdaysMonth = array();
        $i = 0;
        foreach ($dates as $day) {
            // write day date in array field
            $wdaysMonth[$i++] = strftime('%A %e %B %Y', strtotime($day));
        }
        $this->sUsersData = '';
        $this->sUserNames = '<div class="table-responsive card-body m-0 p-0" id="tblUsersCategoriesContainer" '
                . 'style="display: inline-block;" '
                . 'onscroll="syncScrollbars(\'#tblUsersCategoriesContainer\',\'#tblUsersDetailContainer\')">'
                . '<table id="tblUsersCategories" class="calenderTable table table-sm table-striped table-hover m-0 p-0 calenderCategories">'
                . '<tbody>';
        foreach ($userNames as $user) {
            $this->sUserNames .= '<tr> <td><i class="fas fa-users"></i> ' . $user . '</td></tr>';
            $this->sUsersData .= '<tr>';
            $this->getUserEvents($user, $dates);
            $this->sUsersData .= '</tr>';
        }
        $this->sUserNames .=  '</tbody>'
                . '</table>'
                . '</div>';
    }
    public function setProductReportEvents($trackerId, $formId, $month, $dateFormat)
    {
        $dates = $this->getDates($month);
        $this->genericActivites = $this->getCalendarService()->getGeneriActivityList($trackerId, $this->requYMD);
        $colorCode = $this->getCalendarService()->getColorCodesWithFieldMapping($trackerId);
        $list = $this->getProductReportDates($trackerId, $formId);
        $fieldsNames = $list['fields']['field_name'];
        $fieldsLabel = $list['fields']['label'];
        $productReportDates = $list['list'];
        $this->iReportCount = count($productReportDates) > 0 ? count($productReportDates) : 0;
        $this->sReportsDesc = '<div class="table-responsive card-body m-0 p-0" id="tblReportsCategoriesContainer" '
                . 'style="display: inline-block; min-height: 80px; max-height: 150px; overflow-y: scroll;" '
                . 'onscroll="syncScrollbars(\'#tblReportsCategoriesContainer\',\'#tblReportsDetailContainer\')">'
                . '<table id="tblReportsCategories" class="calenderTable table table-sm table-striped table-hover m-0 p-0 calenderCategories">'
                . '<tbody>';
        $this->sReportsData = '';
        foreach ($productReportDates as $row) {
            $cLabel = '';
            foreach ($fieldsNames as $fieldsName) {
                if (date('Y-m-d', strtotime($row[$fieldsName])) != $row[$fieldsName]) {
                    $cLabel .= $row[$fieldsName] . ' - ';
                }
            }
            $cLabel = trim($cLabel, ' - ');
            $this->sReportsDesc .= '<tr><td><i class="fas fa-book"></i> ' . $cLabel . '</td></tr>';
            $this->sReportsData .= '<tr>';
            $this->getProductReportEvents($row, $fieldsNames, $fieldsLabel, $dates, $colorCode, $dateFormat); 
            $this->sReportsData .= '</tr>';
        }        
        $this->sReportsDesc .=  '</tbody>'
                . '</table>'
                . '</div>';        
    }
    public function getProductReportEvents($row, $fieldsNames, $fieldsLabel, $dates, $colorCode, $dateFormat) 
    {
        $bGlobal = false;
        $class = '';
        $now = date('Ymd');
        foreach ($dates as $day) {
            $otherDate = date('Ymd', strtotime($day));
            $bGlobal = false; 
            $title = '';
            $weekdayName = strftime('%a', strtotime($day));
            if ($weekdayName == 'Sat' || $weekdayName == 'Sun') {
                $title = date('l jS \of F Y', strtotime($day)) . ', Weekend';
                if ($now == $otherDate) {
                    $class = 'weekendCell todayCell';
                } else {
                    $class = 'weekendCell';
                }
                $this->sReportsData .= '<td class="' . $class . '" data-toggle="tooltip" data-placement="right" title="' . $title . '"></td>';
            } else {
                foreach ($this->genericActivites as $globalEvent) {
                    $startDate = $globalEvent['start_date'];
                    $endDate = $globalEvent['end_date'];
                    $color = $globalEvent['colour_code'];
                    $desc = $globalEvent['event_name'];
                    
                    if ($startDate !== '' && $endDate !== '') {
                        $checkDate = date('Y-m-d', strtotime($day));
                        if (($checkDate >= $startDate) && ($checkDate <= $endDate)) {
                            if ($now == $otherDate) {
                                $class = 'text-center align-middle todayCell';
                            } else {
                                $class = 'text-center align-middle';
                            }

                            $this->sReportsData .= '<td class="' . $class . '" data-toggle="tooltip" data-placement="right" title="' . date('l jS \of F Y', strtotime($day)) . ', ' . $desc . '">'
                                   . '<i class="act rounded-circle" style="background-color: ' . $color . ';"></i>'
                                   . '</td>'; 
                            $bGlobal = true;
                        }
                    }
                }
                
                if (!$bGlobal) {
                    $searchDate = date('Y-m-d', strtotime($day));
                    $key = array_search($searchDate, $row);
                    
                    if ($key !== false && $key !== '' && array_key_exists($key, $colorCode)) {
                        $colorMap =  $colorCode[$key];
                        if (count($colorMap) > 2) {
                            $color = $colorMap[0][0];
                            $desc = $colorMap[0][1];
                        } else {
                            $color = $colorMap[0];
                            $desc = $colorMap[1];
                        }
                        if ($now == $otherDate) {
                            $class = 'text-center align-middle todayCell';
                        } else {
                            $class = 'text-center align-middle';
                        }
                        
                        $this->sReportsData .= '<td class="' . $class . '" data-toggle="tooltip" data-placement="right" title="' . date('l jS \of F Y', strtotime($day)) . ', ' . $desc . '">'
                                   . '<i class="act rounded-circle" style="background-color: ' . $color . ';"></i>'
                                   . '</td>'; 
                    } else {
                        if ($now == $otherDate) {
                            $this->sReportsData .= '<td class="todayCell"><i class="act rounded-circle" style="opacity: 0.1;"></i></td>';
                        } else {
                            $this->sReportsData .= '<td><i class="act rounded-circle" style="opacity: 0.1;"></i></td>';
                        }
                    }
                }
            }
        }
    }

    public function getUserEvents($username, $dates) 
    {
        $bGlobal = false;
        $class = '';
        $now = date('Ymd');
        foreach ($dates as $day) {
            $otherDate = date('Ymd', strtotime($day));
            $bGlobal = false; 
            $title = '';
            $weekdayName = strftime('%a', strtotime($day));
            if ($weekdayName == 'Sat' || $weekdayName == 'Sun') {
                $title = date('l jS \of F Y', strtotime($day)) . ', Weekend';
                if ($now == $otherDate) {
                    $class = 'weekendCell todayCell';
                } else {
                    $class = 'weekendCell';
                }                
                $this->sUsersData .= '<td class="' . $class . '" data-toggle="tooltip" data-placement="right" title="' . $title . '"></td>';
            } else {
                foreach ($this->genericActivites as $globalEvent) {
                    $startDate = $globalEvent['start_date'];
                    $endDate = $globalEvent['end_date'];
                    $color = $globalEvent['colour_code'];
                    $desc = $globalEvent['event_name'];
                    
                    if ($startDate !== '' && $endDate !== '') {
                        $checkDate = date('Y-m-d', strtotime($day));
                        if (($checkDate >= $startDate) && ($checkDate <= $endDate)) {
                            if ($now == $otherDate) {
                                $class = 'text-center align-middle todayCell';
                            } else {
                                $class = 'text-center align-middle';
                            }                            
                            $this->sUsersData .= '<td class="' . $class . '" data-toggle="tooltip" data-placement="right" title="' . date('l jS \of F Y', strtotime($day)) . ', ' . $desc . '">'
                                   . '<i class="act rounded-circle" style="background-color: ' . $color . ';"></i>'
                                   . '</td>'; 
                            $bGlobal = true;
                        }
                    }
                }
                if (!$bGlobal) {
                    if (isset($this->userActivities['event_details'])) {
                        foreach ($this->userActivities['event_details'] as $userEvent) {
                            if ($userEvent['u_name'] == $username) {
                                $startDate = $userEvent['start_date'];
                                $endDate = $userEvent['end_date'];
                                $color = $userEvent['colour_code'];
                                $desc = $userEvent['event_data'];

                                if ($startDate !== '' && $endDate !== '') {
                                    $checkDate = date('Y-m-d', strtotime($day));
                                    if (($checkDate >= $startDate) && ($checkDate <= $endDate)) {
                                        if ($now == $otherDate) {
                                            $class = 'text-center align-middle todayCell';
                                        } else {
                                            $class = 'text-center align-middle';
                                        }     
                                        $bGlobal = true;
                                        $this->sUsersData .= '<td class="' . $class . '" data-toggle="tooltip" data-placement="right" title="' . date('l jS \of F Y', strtotime($day)) . ', ' . $desc . '">'
                                                   . '<i class="act rounded-circle" style="background-color: ' . $color . ';"></i>'
                                                   . '</td>';                                     
                                    } 
                                }                         
                            }
                        }
                        if (!$bGlobal) {
                            if ($now == $otherDate) {
                                $this->sUsersData .= '<td class="todayCell"><i class="act rounded-circle" style="opacity: 0.1;"></i></td>';
                            } else {
                                $this->sUsersData .= '<td><i class="act rounded-circle" style="opacity: 0.1;"></i></td>';
                            }                                                                                                                                                                        
                        }
                    } else {
                        if ($now == $otherDate) {
                            $this->sUsersData .= '<td class="todayCell"><i class="act rounded-circle" style="opacity: 0.1;"></i></td>';
                        } else {
                            $this->sUsersData .= '<td><i class="act rounded-circle" style="opacity: 0.1;"></i></td>';
                        }                                                                                                            
                    }
                }                
            }
        }
    }

    public function getProductReportDates($trackerId, $formId) 
    {
        $list = $this->getCalendarService()->getProductReportDates($trackerId, $formId);
        return $list;
    }

    public function showMonthsBar($requYMD) 
    {
        $requYear = substr($requYMD, 0, 4);
        $requMonth = substr($requYMD, 5, 2);
        $monthr = $requMonth;
        $timestamp = $requYear . '-' . $monthr;
        // echo $requYMD. $requYear . '-' . $monthr . strftime('%b %Y', strtotime($timestamp)); die;
        $output = '<tr>'
                . ' <td class="calendarNav align-middle p-1">'
                . '<a class="btn btn-outline-secondary rounded-circle float-left p-0 ml-2" title="previous month" onclick="getCalendarData(\'' 
                . date('Y-m', strtotime($requYMD.' -1 month')) . '\')" role="button"> '
                . '<i class="fas fa-angle-left p-1 px-2"></i>'
                . '</a>'
                . '<span>' . strftime('%b %Y', strtotime($timestamp)) . '</span>'
                . '<a class="btn btn-outline-secondary rounded-circle float-right p-0 mr-2" title="next month" onclick="getCalendarData(\''
                . date('Y-m', strtotime($requYMD.' +1 month')) . '\')" role="button"> '
                . '<i class="fas fa-angle-right p-1 px-2"></i>'
                . '</a>'
                . '</td>'
                . '</tr>';

        return $output;
    }

    public function getLegend($trackerId) 
    {
        $res = $this->getCalendarService()->getLegend($trackerId);
        $legend = '<ul class="list-group list-group-flush legend-list">';
        foreach ($res as $li) {
            $legend .= '<li class="list-group-item"><i class="act-legend rounded-circle" style="background-color: ' . $li['colour_code'] . ';"></i>' . $li['event_name'] . '</li>';
        }
        $legend .= '</ul>';
        
        return $legend;
    }

    public function addAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            
            if (!is_numeric($trackerId) || $trackerId == 0 || !is_numeric($formId) || $formId == 0) {
                $this->redirect()->toRoute('tracker');
            }
            $eventTypes = array();
            if ($this->isAdmin($trackerId)) {
                $eventTypes = Array('Global');
                $eventNames = $this->getCalendarService()->getEventNames($trackerId, 'Global');
            } else {
                $eventTypes = Array('User');
                $eventNames = $this->getCalendarService()->getEventNames($trackerId, 'User');
            }
            $trackerResults = $this->getCalendarService()->trackerResults($trackerId);
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return array(
                'trackerResults' => $trackerResults,
                'events' => $eventNames,
                'eventType' => $eventTypes,
                'trackerId' => $trackerId,
                'u_id' => $userContainer->u_id,
            );
        }
    }

    public function eventsListAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            
            if (!is_numeric($trackerId) || $trackerId == 0 || !is_numeric($formId) || $formId == 0) {
                $this->redirect()->toRoute('tracker');
            }
            
            $trackerDetails = $this->getCalendarService()->trackerResults($trackerId);
            $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            return new ViewModel(
                array(
                'trackerResults' => $trackerDetails,
                'trackerId' => $trackerId,
                'formId' => $formId
                )
            );
        }
    }

    public function getMonthDataAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $trackerId = $post['tracker_id'];
            $formId = $post['formId'];
            $month = $post['month'];
            if (!is_numeric($trackerId) || $trackerId == 0 || !is_numeric($formId) || $formId == 0) {
                return $response->setContent(\Zend\Json\Json::encode(array('calendar' => '')));
            }
            $this->iReportCount = 0;
            $this->iUsersCount = 0;   
            $trackerDetails = $this->getCalendarService()->trackerResults($trackerId);
            $arrFormIds = isset($trackerDetails['forms'])?array_column($trackerDetails['forms'], 'form_id'):array();
            if (count($arrFormIds) > 0) {
                if (in_array($formId, $arrFormIds)) {
                    $this->iAccessFormId=$formId;
                } else {
                    $this->iAccessFormId=isset($trackerDetails['forms'][0]['form_id'])?$trackerDetails['forms'][0]['form_id']:0;
                }
            } else {
                $this->iAccessFormId=0;
            }
            
            $this->iFormId = $formId;
            $cal = $this->getAllEvents($trackerId, $formId, $month);
            return $response->setContent(\Zend\Json\Json::encode(array('calendar' => $cal, 'requYMD' => $month, 'reportCount' => $this->iReportCount, 'usersCount' => $this->iUsersCount)));
        }
    }

    public function saveNewEventAction() 
    {
        $response = $this->getResponse();
        $applicationController = new IndexController();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $post = $this->getRequest()->getPost()->toArray();
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = (int) $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
        $match = "/^[a-zA-Z0-9 '.]+$/";
        if (!is_numeric($trackerId) || $trackerId == 0 || !is_numeric($formId) || $formId == 0) {
            $responseCode = 0;
            $errMessage = "Invalid Data";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $this->flashMessenger()->addMessage(array('failure' => 'Error in Adding Event!!!'));
            return $response->setContent(\Zend\Json\Json::encode($resArr));
        } else {
            $data = Array();
            foreach ($post as $key => $value) {
                if ($key != 'reason') {
                    if ($key == 'event_data' && !preg_match($match, $value)) {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = "Invalid Event Description";;
                        $this->flashMessenger()->addMessage(array('failure' => 'Error in Editing Event!!!'));
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        return $response->setContent(\Zend\Json\Json::encode($resArr));
                    }
                    if (($key == 'start_date' || $key == 'end_date') && Date('Y-m-d', strtotime($value)) != $value) {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = ($key == 'start_date') ? "Invalid Start Date" : "Invalid End Date";
                        $this->flashMessenger()->addMessage(array('failure' => 'Error in Editing Event!!!'));
                        return $response->setContent(\Zend\Json\Json::encode($resArr));
                    }                    
                    $data[$key] = $value;
                }
            }
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $data  = filter_var_array($data, FILTER_SANITIZE_STRING);
            $data['customer_id'] = $trackerId;
            $data['u_id'] = ($this->isAdmin($trackerId)) ? 0 : $userContainer->u_id;
            $data['last_modified_time'] = $data['created_time'] = date("Y-m-d H:i:s");
            $checkDup = $this->checkDuplicateEventAction($data, $trackerId, 0);
            if ($checkDup['responseCode'] == 1) {
                $res = $this->getCalendarService()->saveEvent($data);
    
                if ($res !== null) {
                    $responseCode = 1;
                    $errMessage = "Success";
                    $resArr['responseCode'] = $responseCode;
                    $resArr['errMessage'] = $errMessage;
                    $this->getAuditService()->saveToLog(((isset($res)) ? $res : 0), $userDetails['email'], 'Add Event', '', "{'eventid':'" . $data['event_id'] . "', 'eventdata':" . $data['event_data'] . ", 'startdate':'" . (date('Y-M-d', strtotime($data['start_date']))) . ", 'enddate':'" . (date('Y-M-d', strtotime($data['end_date']))) . "'}", $post['reason'], 'Success', $data['customer_id']);
                    $this->flashMessenger()->addMessage(array('success' => 'Event Added successfully!'));
                } else {
                    $responseCode = 0;
                    $errMessage = "Failed";
                    $resArr['responseCode'] = $responseCode;
                    $resArr['errMessage'] = $errMessage;
                    $this->getAuditService()->saveToLog(((isset($res)) ? $res : 0), $userDetails['email'], 'Add Event', '', "{'eventid':'" . $data['event_id'] . "', 'eventdata':" . $data['event_data'] . ", 'startdate':'" . (date('Y-M-d', strtotime($data['start_date']))) . ", 'enddate':'" . (date('Y-M-d', strtotime($data['end_date']))) . "'}", $post['reason'], 'Failed', $data['customer_id']);
                    $this->flashMessenger()->addMessage(array('failure' => 'Error in Adding Event!!!'));
                }
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
                return $response->setContent(\Zend\Json\Json::encode($resArr));
            } else {
                return $response->setContent(\Zend\Json\Json::encode($checkDup));
            }
        }
    }

    public function deleteEventAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $applicationController = new IndexController();
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        $res = $this->getCalendarService()->deleteEvent($post['event_id']);
        if ($res != 0) {
            $responseCode = 1;
            $errMessage = "Success";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $this->getAuditService()->saveToLog(((isset($post['event_id'])) ? $post['event_id'] : 0), $userDetails['email'], 'Delete Event', "{'id':'" . $post['event_id'] . "', 'is_archived':0 }", "{'id':'" . $post['event_id'] . "', 'is_archived':1 }", $post['comment'], 'Success', $post['tracker_id']);
            $this->flashMessenger()->addMessage(array('success' => 'Event Deleted successfully!'));
        } else {
            $responseCode = 0;
            $errMessage = "Failed";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $this->getAuditService()->saveToLog(((isset($post['event_id'])) ? $post['event_id'] : 0), $userDetails['email'], 'Delete Event', "{'id':'" . $post['event_id'] . "', 'is_archived':0 }", '', $post['comment'], 'Failed', $post['tracker_id']);
            $this->flashMessenger()->addMessage(array('failure' => 'Error in Deleting Event!!!'));
        }        
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function editAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $eventId = (int) $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $eventData = $this->getCalendarService()->getEventData($eventId);
            $eventType = (isset($eventData[0]))?$eventData[0]['event_type']:'';
            if (($this->isAdmin($trackerId) && $eventType == 'Global' ) || (!$this->isAdmin($trackerId) && $eventType == 'User')) {
                $eventNames = $this->getCalendarService()->getEventNames($trackerId, $eventType);
                $trackerResults = $this->getCalendarService()->trackerResults($trackerId);
                $session->setSession('eventData', array('eventData' => $eventData));
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
                return array(
                    'trackerResults' => $trackerResults,
                    'events' => $eventNames,
                    'eventData' => $eventData,
                    'trackerId' => $trackerId,
                    'eventId' => $eventId,
                    'u_id' => $userContainer->u_id,
                );
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
    }

    public function saveEditEventAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $eventDataContainer = $session->getSession('eventData');
        $userDetails = $userContainer->user_details;
        $applicationController = new IndexController();
        $post = $this->getRequest()->getPost()->toArray();
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = (int) $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
        $id = filter_var($post['id'], FILTER_SANITIZE_STRING);
        $oldValues = $eventDataContainer->eventData;
        $session->clearSession('eventData');
        $match = "/^[a-zA-Z0-9 '.]+$/";
        $data = Array();
        foreach ($post as $key => $value) {
            if ($key != 'id' && $key != 'reason') {
                if ($key == 'event_data' && !preg_match($match, $value)) {
                    $resArr['responseCode'] = 0;
                    $resArr['errMessage'] = "Invalid Event Description";;
                    $this->flashMessenger()->addMessage(array('failure' => 'Error in Editing Event!!!'));
                    return $response->setContent(\Zend\Json\Json::encode($resArr));
                }
                if (($key == 'start_date' || $key == 'end_date') && Date('Y-m-d', strtotime($value)) != $value) {
                    $resArr['responseCode'] = 0;
                    $resArr['errMessage'] = ($key == 'start_date') ? "Invalid Start Date" : "Invalid End Date";
                    $this->flashMessenger()->addMessage(array('failure' => 'Error in Editing Event!!!'));
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    return $response->setContent(\Zend\Json\Json::encode($resArr));
                }
                
                $data[$key] = htmlentities($value);
            }
        }
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        $data  = filter_var_array($data, FILTER_SANITIZE_STRING);        
        $data['customer_id'] = $trackerId;
        $data['u_id'] = ($this->isAdmin($trackerId)) ? 0 : $userContainer->u_id;
        $data['last_modified_time'] = date("Y-m-d H:i:s");
        $checkDup = $this->checkDuplicateEventAction($data, $trackerId, $id);
        if ($checkDup['responseCode'] == 1) {
            $res = $this->getCalendarService()->saveEditEvent($data, $id); //print_r($res);die;
            switch ($res) {
            case 1:
                $responseCode = 1;
                $errMessage = "Success";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
                if (!empty($oldValues[0])) {
                    $this->getAuditService()->saveToLog(intval($id), $userDetails['email'], 'Edit Event', "{'eventid':'" . ($oldValues[0]['event_id']) . "', 'eventdata':" . ($oldValues[0]['event_data']) . ", 'startdate':'" . (date('Y-M-d', strtotime($oldValues[0]['start_date']))) . ", 'enddate':'" . (date('Y-M-d', strtotime($oldValues[0]['end_date']))) . "'}", "{'eventid':'" . $data['event_id'] . "', 'eventdata':" . ($data['event_data']) . ", 'startdate':'" . (date('Y-M-d', strtotime($data['start_date']))) . ", 'enddate':'" . (date('Y-M-d', strtotime($data['end_date']))) . "'}", ($post['reason']), 'Success', $data['customer_id']);
                }
                $this->flashMessenger()->addMessage(array('success' => 'Event Updated successfully!'));
                break;
            case 0:
                $responseCode = 0;
                $errMessage = "Failed";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
                if (!empty($oldValues[0])) {
                    $this->getAuditService()->saveToLog(intval($id), $userDetails['email'], 'Edit Event', "{'eventid':'" . $oldValues[0]['event_id'] . "', 'eventdata':" . ($oldValues[0]['event_data']) . ", 'startdate':'" . (date('Y-M-d', strtotime($oldValues[0]['start_date']))) . ", 'enddate':'" . (date('Y-M-d', strtotime($oldValues[0]['end_date']))) . "'}", "{'eventid':'" . $data['event_id'] . "', 'eventdata':" . ($data['event_data']) . ", 'startdate':'" . (date('Y-M-d', strtotime($data['start_date']))) . ", 'enddate':'" . (date('Y-M-d', strtotime($data['end_date']))) . "'}", ($post['reason']), 'Failed', $data['customer_id']);
                }
                $this->flashMessenger()->addMessage(array('failure' => 'Error in updating Event!!!'));
                break;
            default:
                break;
            }
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return $response->setContent(\Zend\Json\Json::encode($resArr));
        } else {
            return $response->setContent(\Zend\Json\Json::encode($checkDup));
        }
    }

    public function checkDuplicateEventAction($data, $trackerId, $id) 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $uId = ($this->isAdmin($trackerId)) ? 0 : $userContainer->u_id;
        $res = $this->getCalendarService()->checkDuplicateEvent($uId, $trackerId, $id);
        $responseCode = 1;
        $errMessage = "Available";
        foreach ($res as $event) {
            if ($data['start_date'] >= $event['start_date'] && $data['end_date'] <= $event['end_date'] || $data['start_date'] <= $event['start_date'] && $data['end_date'] >= $event['end_date']) {
                $responseCode = 0;
                $errMessage = "The Event is overlapping with the dates of other Event with Name: " . $event['event_name'] . " and Description:" . $event['event_data'];
                break;
            } else if ($data['end_date'] == $event['start_date'] || $data['start_date'] <= $event['start_date'] && $data['end_date'] >= $event['start_date']) {
                $responseCode = 0;
                $errMessage = "The Event's End Date is overlapping with the dates of other Event with Name: " . $event['event_name'] . ' and Description:' . $event['event_data'];
                break;
            } else if ($data['start_date'] == $event['end_date'] || $data['start_date'] <= $event['end_date'] && $data['end_date'] >= $event['end_date']) {
                $responseCode = 0;
                $errMessage = "The Event's Start Date is overlapping with the dates of other Event with Name: " . $event['event_name'] . ' and Description:' . $event['event_data'];
            }
        }
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }

    public function fetchAllDataAction() 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');        
        $response = $this->getResponse();
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = (int) $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
        $configData=$this->getCalendarService()->getconfigDataByForm($formId);
        $configData = array_column($configData, 'config_value', 'config_key');
        if ($this->isAdmin($trackerId)) {
            $list = $this->getCalendarService()->getHolidayList($trackerId, 'Global', 0);
        } else {
            $list = $this->getCalendarService()->getHolidayList($trackerId, 'User', $userContainer->u_id);
        }
        foreach ($list as $k => $v) {
            if (Date('Y-m-d', strtotime($v['start_date'])) == $v['start_date']) {
                $list[$k]['start_date']=Date('d-M-Y', strtotime($list[$k]['start_date'])); 
            }
            if (Date('Y-m-d', strtotime($v['end_date'])) == $v['end_date']) {
                $list[$k]['end_date']=Date('d-M-Y', strtotime($list[$k]['end_date'])); 
            }
            $list[$k]['event_data'] = html_entity_decode($list[$k]['event_data'], ENT_QUOTES | ENT_HTML5);
        }
        
        return $response->setContent(\Zend\Json\Json::encode($list));
    }
    
    public function changeMonthBarAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $requYMD=$post['month'];
            $requYear = substr($requYMD, 0, 4);
            $requMonth = substr($requYMD, 5, 2);
            $monthr = $requMonth < 7 ? 1 : 7;
            $timestamp = $requYear . '-' . $monthr;

            $monthOut = array();
            $c = 0;
            $monthOut[$c][0] = date('Y-m', strtotime($timestamp));
            $monthOut[$c++][1] = strftime('%b %Y', strtotime($timestamp));
            $monthOut[$c][0] = date('Y-m', strtotime($timestamp . ' +1 month')); // next month
            $monthOut[$c++][1] = strftime('%b %Y', strtotime($timestamp . ' +1 month'));
            $monthOut[$c][0] = date('Y-m', strtotime($timestamp . ' +2 month'));
            $monthOut[$c++][1] = strftime('%b %Y', strtotime($timestamp . ' +2 month'));
            $monthOut[$c][0] = date('Y-m', strtotime($timestamp . ' +3 month'));
            $monthOut[$c++][1] = strftime('%b %Y', strtotime($timestamp . ' +3 month'));
            $monthOut[$c][0] = date('Y-m', strtotime($timestamp . ' +4 month'));
            $monthOut[$c++][1] = strftime('%b %Y', strtotime($timestamp . ' +4 month'));
            $monthOut[$c][0] = date('Y-m', strtotime($timestamp . ' +5 month'));
            $monthOut[$c++][1] = strftime('%b %Y', strtotime($timestamp . ' +5 month'));
            $c_out = 0;

            $header = '<button type="button" class="mnthbtn btn btn-primary ' . ((substr($requYMD, 0, 7) == $monthOut[$c_out][0]) ? "btn-info" : "") . '" value="' . $monthOut[$c_out][0] . '" id="' . $monthOut[$c_out][0] . '">' . $monthOut[$c_out++][1] . '</button>';
            $header .= '<button type="button" class="mnthbtn btn btn-primary ' . ((substr($requYMD, 0, 7) == $monthOut[$c_out][0]) ? "btn-info" : "") . '" value="' . $monthOut[$c_out][0] . '" id="' . $monthOut[$c_out][0] . '">' . $monthOut[$c_out++][1] . '</button>';
            $header .= '<button type="button" class="mnthbtn btn btn-primary ' . ((substr($requYMD, 0, 7) == $monthOut[$c_out][0]) ? "btn-info" : "") . '" value="' . $monthOut[$c_out][0] . '" id="' . $monthOut[$c_out][0] . '">' . $monthOut[$c_out++][1] . '</button>';
            $header .= '<button type="button" class="mnthbtn btn btn-primary ' . ((substr($requYMD, 0, 7) == $monthOut[$c_out][0]) ? "btn-info" : "") . '" value="' . $monthOut[$c_out][0] . '" id="' . $monthOut[$c_out][0] . '">' . $monthOut[$c_out++][1] . '</button>';
            $header .= '<button type="button" class="mnthbtn btn btn-primary ' . ((substr($requYMD, 0, 7) == $monthOut[$c_out][0]) ? "btn-info" : "") . '" value="' . $monthOut[$c_out][0] . '" id="' . $monthOut[$c_out][0] . '">' . $monthOut[$c_out++][1] . '</button>';
            $header .= '<button type="button" class="mnthbtn btn btn-primary ' . ((substr($requYMD, 0, 7) == $monthOut[$c_out][0]) ? "btn-info" : "") . '" value="' . $monthOut[$c_out][0] . '" id="' . $monthOut[$c_out][0] . '">' . $monthOut[$c_out++][1] . '</button>';

            //                    $header .= '<button type="button" class="btn btn-primary" id="datepickbtn">Calender<input id="datepicker" name="request" type="text" value="'.substr($requYMD,0,7).'" /><i class="lnr icon-calendar-full"></i></button>';

        }
        return $response->setContent(\Zend\Json\Json::encode(array('header'=>$header)));
    }
}
