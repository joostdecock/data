<?php
/** App\Tools\MigrationKit class */
namespace App\Tools;

/**
 * The MigrationKit class.
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class MigrationKit 
{

    // constructor receives container instance
    public function __construct(\Slim\Container $container) 
    {
        $this->container = $container;
    }
    
    /** 
     * Migrates data from a MMP user to freesewing
     *
     * @param int $id the user id
     * @param array $mmp Array of mmp data for this user
     *
     * @return FIXME
     */
    public function migrate(\App\Data\User $user) 
    {
        $this->account = $this->loadAccount($user->getEmail());

        // Is this user known on MMP?
        if(!is_object($this->account)) return false; // Nope

        // Migrate username
        $user->setUsername($this->account->username);
        
        // Migrate user picture
        $avatarKit = $this->container->get('AvatarKit');
        if(isset($this->account->picture) && $this->account->picture != '') {
            $user->setPicture($avatarKit->createFromMmp(json_decode($this->account->picture), $user->getHandle(), 'user')); 
        }

        // Mark user as migrated
        $user->setMigrated(date("Y-m-d H:i:s", $this->account->created));

        // Save user
        $user->save();

        // Migrating models
        foreach($this->loadModels() as $model) $this->migrateModel($model, $user);
    
        return true;
    }

    /**
     * Loads a MMP user id based on their email address
     *
     * Note: This can in principle return 0 (which would evaluates 
     * to false). But that's ok as user 0 is the Drupal 
     * superuser and thus should not be migrated
     *
     * @param string $email The email address of the user
     *
     * @return int|false The uid of the MMP user or false if user does not exist
     */
    private function loadAccount($email) {
        $db = $this->container->get('db');
        $sql = "SELECT * from `mmpusers` WHERE `email` =".$db->quote($email);
        
        return $db->query($sql)->fetch(\PDO::FETCH_OBJ);
    }


    private function loadModels() {
        $db = $this->container->get('db');
        $sql = "SELECT * FROM `mmpmodels` WHERE `uid` = ".$db->quote($this->account->uid).";";
        
        return $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
    }

    private function migrateModel($oldModel, $user) {
        $newModel = $this->container->get('Model');

        // Create new model
        $newModel->create($user->getId());

        // Set basic info
        $newModel->setName($oldModel->title);
        if($oldModel->sex === '0') $newModel->setBody('male');
        else if($oldModel->sex === '1') $newModel->setBody('female');

        // Migrate model picture
        $avatarKit = $this->container->get('AvatarKit');
        if(isset($oldModel->picture) && $oldModel->picture != '') {
            $newModel->setPicture($avatarKit->createFromMmp(json_decode($oldModel->picture), $newModel->getHandle(), 'model')); 
        }
        
        // Migrate measurements
        $newModel->setData($this->migrateMeasurements(json_decode($oldModel->data)));

        // MMP models are always metric
        $newModel->setUnits('metric');

        // Save model
        $newModel->save();
    }

    private function migrateMeasurements($measurements) 
    {
        // Object to hold our migrated data
        $data = new \stdClass();
        $data->measurements = [];
        $data->info=[];
        $data->info['migrated'] = "From makemypattern.com";
        
        $map = [
            'field_across_back_width'          => 'acrossBack',
            'field_biceps_circumference'       => 'bicepsCircumference',
            'field_body_rise'                  => 'bodyRise',
            'field_center_back_neck_to_waist'  => 'centerBackNeckToWaist',
            'field_chest'                      => 'chestCircumference',
            'field_inseam'                     => 'inseam',
            'field_natural_waist'              => 'naturalWaist',
            'field_natural_waist_to_hip'       => 'naturalWaistToHip',
            'field_natural_waist_to_underbust' => 'naturalWaistToUnderbust',
            'field_neck_size'                  => 'neckCircumference',
            'field_seat_circumference'         => 'seatCircumference',
            'field_shoulder_slope'             => 'shoulderSlope',
            'field_shoulder_to_elbow_length'   => 'shoulderToElbow',
            'field_sleeve_length'              => 'sleeveLengthToWrist',
            'field_neck_to_waist'              => 'centerBackNeckToWaist',
            'field_trouser_waist_circum'       => 'hipsCircumference',
            'field_underbust'                  => 'underBust',
            'field_uppermost_leg_circumferenc' => 'upperLegCircumference',
            'field_waistline_to_waistline_cro' => 'crossseamLength',
            'field_wrist_circumference'        => 'wristCircumference',
        ];

        foreach($map as $old => $new) {
            if(isset($measurements->$old)) {
                $data->measurements[$new] = $measurements->$old;
            }
        }

        return $data;
    }       
}
