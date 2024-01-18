<?php

namespace SigTRACE\SignalCalendarTest\Controller;

use SignalCalendarTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use SigTRACE\SignalCalendar\Controller\IndexController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;
use Common\Role\Controller\RoleController;

class SignalCalendarControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $userContainer;
    protected $trackerContainer;
    protected $session;
    
    protected $traceError = true;
    
    public function setUp()
    {
        @session_start();
        parent::setUp();
        $session = new SessionContainer();
        $this->userContainer    =   $session->getSession('user');
        $this->trackerContainer =   $session->getSession('tracker');
        
        $serviceManager         =   Bootstrap::getServiceManager();
        $this->controller       =   new IndexController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Index'));
        $this->event            =   new MvcEvent();
        $config                 =   $serviceManager->get('Config');
        $routerConfig           =   isset($config['router']) ? $config['router'] : array();
        $router                 =   HttpRouter::factory($routerConfig);
        
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
    }
    public function tearDown()
    {
        $session = new SessionContainer();
        $session->clearSession('user');
        $session->clearSession('tracker');
    }
    
    public function testGetServiceReturnsAnInstanceOfCasedataModel()
    {
        $this->assertInstanceOf('SigTRACE\SignalCalendar\Model\Casedata', $this->controller->getCasedataService());
    }

    public function testGetServiceReturnsAnInstanceOfCasedataService()
    {
        $this->assertInstanceOf('SigTRACE\SignalCalendar\Service\CasedataService', $this->controller->getService());
    }

    public function testGetServiceReturnsAnInstanceOfCasdataeHelper()
    {
        $this->assertInstanceOf('SigTRACE\SignalCalendar\Helper\CasedataHelper', $this->controller->getCasedataHelperService());
    }

    public function testgetFileFromClientCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'readImportFile');

        $this->routeMatch->setParam('trackerId', 109);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testgetFileFromCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'processImportFile');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testgetFileFromCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'processImportFile');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'trackerId' => 109, 
                        'formId' => 199, 
                        'header' => json_encode(array("CASE_ID","NVL","ACTIVE INGREDIENT","sd_meeting_date","trgt_date_qualitative","trgt_date_review_qualitative","VERSION_NO","AER_FOLLOWUP_DATE","CASE_TYPE","SERIOUSNESS COMPANY","SERIOUSNESS REPORTER","PT_NAME","SOC_NAME","REACTION RANK","INDICATION PT","INDICATION SOC","LAB RESULT","SERIOUSNESS_EVENT","SOURCE_AER_SOURCE","PATIENT_DATA","OUTCOME_COMPANY","STUDY_PATIENT_NO","PROTOCOL_NO","NARRATIVE","WEIGHT","HEIGHT","AER COUNTRY","PATIENT SEX","DEATH","LIFE THREATENING","HOSPITALIZATION","DISABILITY","CONGENITAL ANOMALY","OTHER MEDICALLY SIGNIFICANT","SUSPECT DOSE INFORMATION","RMC Grade","Reporter Causality","Company Causality","OUTCOME AFTER REINTRO","DECHALLENGE","RECHALLENGE","AE Onset date","AE Cessation date","AE DURATION","MANUFACTURE RECV DATE","STUDY PATIENT NO","MEDICALLY CONFIRMED","CENTER_NO","OUTCOME REPORTER","Initial receipt date")),
                        'data' => json_encode(array(0 => array("VIT-2016-01712","Veltassa","patiromer","14/11/2019","14/11/2019","14/11/2019","3","10-Jul-19","Spontaneous","Not Serious","Not Serious","Constipation","Gastrointestinal disorders","7","HYPERKALAEMIA,\nESSENTIAL (PRIMARY) HYPERTENSION,\nCHRONIC KIDNEY DISEASE, STAGE 4 (SEVERE)\n","Metabolism and nutrition disorders,\nVascular disorders,\nRenal and urinary disorders\n","BLOOD SUGAR-40-Unknown-Unknown\nBLOOD SUGAR-50-Unknown-Unknown\n","Not Serious","Spontaneous\n,Company representative\n,Company representative\n,Company representative\n","AS; Elderly; 73 Years; Female; 17-03-1943","","","","This initial spontaneous report was received on 24-Mar-2016 from a consumer in USA via a company representative (RLY2016000263/495254/167315) and concerns a 73-year old elderly female patient (Weight: 237 pounds and height: 63 inches) with medical history of diabetes, heart problem and kidney problem. The patient was concomitantly taking acetylsalicylic acid (Aspirin), ranolazine (Ranexa), Isosorbide mononitrate, Carvedilol, Lisinopril, Bumetanide, simvastatin (Zocor), Fenofibrate, glyceryl trinitrate (Nitrostat), Omeprazole, Levothyroxine sodium, sodium polystyrene sulfonate (Kionex), Amoxicillin, Magnesium oxide, insulin human injection, isophane/insulin human (Novolin 70/30), Gabapentin, Cyclobenzaprine hydrochloride, Buspirone hydrochloride, nortriptyline hydrochloride, oxycodone hydrochloride/ paracetamol (Endocet), colchicine (Colcrys), Allopurinol, calcium citrate (Calcitrate), cyanocobalamin (Vitamin B12), Calcium carbonate, colecalciferol (Vitamin D3), Fluorouracil, betamethasone dipropionate/ clotrimazole (Lotrisone), Triamcinolone acetonide, Hydroxyzine embonate, Hydroxyzine hydrochloride, Ketoconazole, albendazole (Azole), clopidogrel bisulfate (Plavix) and ferumoxytol (Feraheme) for an unknown indication.\n\nOn 24-Mar-2016, the patient started therapy with oral patiromer (Veltassa) at a dose of 8.4 gram on Tuesdays, Thursdays and Saturdays (PT: Inappropriate schedule of product administration) for hyperkalaemia.\n\nSince starting Veltassa, the patient experienced low blood sugar/ blood sugars dropping as low as 40 (PT: Blood glucose decreased) and vomited (PT: Vomiting). On the same day, the patient blood sugar was 50 (Unit and reference range not provided). No information was provided on remedial therapy.\n\nOn 25-Mar-2016, the patient started therapy with oral Veltassa at a dose of 8.4 gram once daily for hyperkalemia/ essential (primary hypertension) and chronic kidney disease, stage 4 (severe).\n\nOn an unspecified date, the patient was sick (PT: Malaise) and had to stop taking Veltassa for a couple of days and then restarted. The MD was aware. No information was provided on remedial therapy.\n\nOn 16-Nov-2017, the patient started therapy with oral Veltassa, at a dose of 8.4 gram three times per week for hyperkalaemia.\n\nOn an unspecified date, the patient experienced constipation (PT: Constipation). The MD was aware. No information was provided on remedial therapy.\n\nOn 27-Feb-2018, the patient forgot to take medication a couple of times (PT: Product dose omission).\n\nOn an unspecified date in 2018, the patient experienced infection in her groin (PT: Groin infection). No information was provided on remedial therapy.\n\nOn 09-Jul-2019, the patient forgot to take the medication. It was unknown if side effects had occurred or not. MD was not aware of the event.\n\nThe therapy with Veltassa was ongoing at the time of report.\n\nThe patient had not yet recovered from the event constipation at the time of report. The outcome of the events blood glucose decreased, vomiting, malaise and groin infection was unknown. \n\nThis case was not serious.\n\nThe reporter provided the causality assessment for the event constipation to be not related with Veltassa. The reporter did not provide the causality assessment for the events blood glucose decreased, vomiting, constipation, malaise and groin infection with Veltassa therapy.\n\nCausality evaluation by company:\n\nThe events blood glucose decreased, malaise and groin infection are unlisted and the events constipation and vomiting are listed according to Veltassa Company Core Data Sheet (CCDS).\nThe events blood glucose decreased, malaise and groin infection are unexpected and the events constipation and vomiting are expected according to Veltassa US-PI.\nThe events blood glucose decreased, malaise and groin infection are unexpected and the events constipation and vomiting are expected according to Veltassa Canada Labeling Assessment.\nThe events blood glucose decreased, malaise and groin infection are unexpected and the events constipation and vomiting are expected according to Veltassa Swiss Medic.\n\nThe time to onset between Veltassa administration and the events reported makes a temporal relationship with Veltassa to the events as plausible. The patient's medical history and concomitant medications could have contributed in the events reported. The company assessed the events vomiting and malaise as possible related and the events blood glucose decreased, groin infection as unlikely related and agrees with the reporter and assessed the event constipation as not related to Veltassa according with the adapted WHO-Uppsala Monitoring Center Standardized Causality Assessment System.\n\n***Reports VIT-2018-02121, VIT-2017-13172, VIT-2017-03348 and VIT-2016-01712 have been found to be duplicates. All information from VIT-2018-02121, VIT-2017-13172, VIT-2017-03348 have been incorporated into VIT-2016-01712. Reports VIT-2018-02121, VIT-2017-13172, VIT-2017-03348 will be logically deleted from the safety database. Report VIT-2016-01712 will be kept for future references.\n\n\n***Follow up information was received on 23-Jul-2018 from consumer:\n- Concomitant medication details were provided (Plavix).\n- Veltassa therapy details were updated (Indication).\n- Event verbatim was updated from low blood sugar to low blood sugar/ blood sugars dropping.\n\n***Follow up information was received on 21-Sep-2018 from the consumer:\n- Medical confirmation was not provided.\n- Patient demographic details were updated (Weight and height).\n- Medical history details were provided (Diabetes, heart problem and kidney problem).\n- The event bronchitis was deleted as it was confirmed by the reporter that the patient did not experience bronchitis (PT: Bronchitis).\n- Event verbatim low blood sugar/ blood sugars dropping was updated to low blood sugar to low blood sugar/ blood sugars dropping as low as 40.\n- Event infection (non-specific) (PT: Infection) amended to infection in groin (PT: Groin infection).\n- The outcome of the event constipation (PT: Constipation) was provided as not recovered.\n- Reporter causality assessment for the event constipation was provided as not related.\n\n***Significant follow up information was received on 10-Jul-2019 from the consumer:\n- Additional log number was provided.\n- The second episode of product dose omission was reported.\n- A correction of change in Veltassa therapy start date was made, coding for the event 8.4 gram on Tuesdays, Thursdays and Saturdays was amended from (PT: Off-label use) to (PT: Inappropriate schedule of product administration) and deletion of one reporter tab was made as both are same reporter.","237 Pound","63 Inch","United States of America","Female","","","","","","","24/Mar/2016 - \r \r8.4 Gram\n25/Mar/2016 - \r \r8.4 Gram\n16/Nov/2017 - \r \r8.4 Gram\n","","Not Related","Not Related","","Not Applicable","Not Applicable","",""," ","11-Jul-19","","Yes","","Not recovered/not resolved","24-Mar-16")))
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }    
    
    public function testreadErmrImportFileForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'readErmrImportFile');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
}
