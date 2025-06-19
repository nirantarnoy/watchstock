<?php

namespace backend\controllers;

use common\models\LoginForm;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error','logindriver'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'changepassword','grab','logoutdriver'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */


    public function actionIndex()
    {

        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            //echo "login ok"; return;
            // return $this->goBack();
            $model_user_info = \backend\models\User::find()->where(['id' => \Yii::$app->user->id])->one();
            if($model_user_info){
                if($model_user_info->user_group_id == 3){
                    \Yii::$app->user->logout();
                }
            }
            return $this->redirect(['site/index']);
        }

        //   $model->password = '';
        $model->password = '';
        $this->layout = 'main_login';
        $model->password = '';
        return $this->render('login_new', [
            'model' => $model,
        ]);


    }




    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        \Yii::$app->user->logout();

//        if(isset($_SESSION['driver_login'])){
//            return $this->redirect(['site/logindriver']);
//        }

        return $this->goHome();
    }
    public function actionLogoutdriver()
    {
        \Yii::$app->user->logout();

        return $this->redirect(['site/logindriver']);
    }


    public function actionChangepassword()
    {
        $model = new \backend\models\Resetform();
        if ($model->load(Yii::$app->request->post())) {

            $model_user = \backend\models\User::find()->where(['id' => Yii::$app->user->id])->one();
            if ($model->oldpw != '' && $model->newpw != '' && $model->confirmpw != '') {
                if ($model->confirmpw != $model->newpw) {
                    $session = Yii::$app->session;
                    $session->setFlash('msg_err', 'รหัสยืนยันไม่ตรงกับรหัสใหม่');
                } else {
                    if ($model_user->validatePassword($model->oldpw)) {
                        $model_user->setPassword($model->confirmpw);
                        if ($model_user->save()) {
                            $session = Yii::$app->session;
                            $session->setFlash('msg_success', 'ทำการเปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
                            return $this->redirect(['site_/logout']);
                        }
                    } else {
                        $session = Yii::$app->session;
                        $session->setFlash('msg_err', 'รหัสผ่านเดิมไม่ถูกต้อง');
                    }
                }

            } else {
                $session = Yii::$app->session;
                $session->setFlash('msg_err', 'กรุณาป้อนข้อมูลให้ครบ');
            }

        }
        return $this->render('_setpassword', [
            'model' => $model
        ]);
    }

    public function actionGrab()
    {

        $aControllers = [];


        // $path = \Yii::$app->getBasePath() . 'icesystem/';
        $path = \Yii::$app->basePath;

        $ctrls = function ($path) use (&$ctrls, &$aControllers) {

            $oIterator = new \DirectoryIterator($path);

            foreach ($oIterator as $oFile) {

                if (!$oFile->isDot()

                    && (false !== strpos($oFile->getPathname(), 'controllers')

                        || false !== strpos($oFile->getPathname(), 'modules')

                    )

                ) {


                    if ($oFile->isDir()) {

                        $ctrls($oFile->getPathname());

                    } else {

                        if (strpos($oFile->getBasename(), 'Controller.php')) {


                            $content = file_get_contents($oFile->getPathname());

                            $controllerName = $oFile->getBasename('.php');


                            $route = explode(\Yii::$app->basePath, $oFile->getPathname());

                            $route = str_ireplace(array('modules', 'controllers', 'Controller.php'), '', $route[1]);

                            $route = preg_replace("/(\/){2,}/", "/", $route);


                            $aControllers[$controllerName] = [

                                'filepath' => $oFile->getPathname(),

                                'route' => mb_strtolower($route),

                                'actions' => [],

                            ];

                            preg_match_all('#function action(.*)\(#ui', $content, $aData);


                            $acts = function ($aData) use (&$aControllers, &$controllerName) {


                                if (!empty($aData) && isset($aData[1]) && !empty($aData[1])) {


                                    $aControllers[$controllerName]['actions'] = array_map(

                                        function ($actionName) {
                                            return mb_strtolower(trim($actionName, '{\\.*()'));
                                        },

                                        $aData[1]

                                    );


                                }

                            };


                            $acts($aData);

                        }

                    }


                }

            }

        };


        $ctrls($path);


        echo '<pre>';

        //   print_r($aControllers);

        foreach ($aControllers as $value) {

            //  $route_name = substr($value['route'],2);
            $route_name = substr($value['route'], 1);
            for ($x = 0; $x <= count($value['actions']) - 1; $x++) {
                $fullname = $route_name . '/' . $value['actions'][$x];
                if ($fullname != '') {
                    $chk = \common\models\AuthItem::find()->where(['name' => $fullname])->one();
                    if ($chk) continue;

                    $model = new \common\models\AuthItem();
                    $model->name = $fullname;
                    $model->type = 2;
                    $model->description = '';
                    $model->created_at = time();
                    $model->save(false);
                }
                echo $fullname . '<br/>';

            }
            //echo $route_name;
            // print_r($value['route']);
        }
        // print_r($aControllers['AdjustmentController']);

    }

}
