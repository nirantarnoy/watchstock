<?php
namespace backend\models;
class Resetform extends \yii\base\Model{

    public $oldpw,$newpw,$confirmpw;

    public function rules()
    {
       return[
           [['oldpw','newpw','confirmpw'],'required'],
           ['confirmpw', 'compare', 'compareAttribute' => 'newpw'],
       ];
    }
    public function attributeLabels()
    {
        return [
            'oldpw'=>'รหหัสผ่านเดิม',
            'newpw'=>'รหัสผ่านใหม่',
            'confirmpw'=>'ยืนยันรหัสผ่าน'
        ];
    }
}
