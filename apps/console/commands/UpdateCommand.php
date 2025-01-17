<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * UpdateCommand
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.4.8
 */
 
class UpdateCommand extends CConsoleCommand 
{
    public function actionIndex() 
    {
        $options        = Yii::app()->options;
        $versionInFile  = MW_VERSION;
        $versionInDb    = $options->get('system.common.version');
        
        if (!version_compare($versionInFile, $versionInDb, '>')) {
            if ($options->get('system.common.site_status', 'online') != 'online') {
                $options->set('system.common.site_status', 'online');
            }
            echo Yii::t('update', "You are already at latest version!") . "\n";
            exit(0);
        }
        
        $input = $this->confirm(Yii::t('update', 'Are you sure you want to update your Mailwizz application from version {vFrom} to version {vTo} ?', array(
            '{vFrom}' => $versionInDb,
            '{vTo}'   => $versionInFile,
        )));
        
        if (!$input) {
            echo "\n" . Yii::t('update', "Okay, aborting the update process!") . "\n";
            exit(0);
        }
        
        // put the application offline
        $options->set('system.common.site_status', 'offline');
        
        $workersPath = Yii::getPathOfAlias('backend.components.update');
        require_once $workersPath . '/UpdateWorkerAbstract.php';
        
        $updateWorkers  = (array)FileSystemHelper::readDirectoryContents($workersPath);
            
        foreach ($updateWorkers as $index => $fileName) {
            $fileName = basename($fileName, '.php');
            if (strpos($fileName, 'UpdateWorkerFor_') !== 0) {
                unset($updateWorkers[$index]);
                continue;
            }
            
            $workerVersion = str_replace('UpdateWorkerFor_', '', $fileName);
            $workerVersion = str_replace('_', '.', $workerVersion);
            
            // previous versions ?
            if (version_compare($workerVersion, $versionInDb, '<=')) {
                unset($updateWorkers[$index]);
                continue;
            }
            
            // next versions ?
            if (version_compare($workerVersion, $versionInFile, '>')) {
                unset($updateWorkers[$index]);
                continue;
            }
            
            $updateWorkers[$index] = $workerVersion;
        }
        
        sort($updateWorkers, SORT_NUMERIC | SORT_ASC);
            
        $db = Yii::app()->getDb();
        $db->createCommand('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0')->execute();
        $db->createCommand('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0')->execute();
        $db->createCommand('SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=""')->execute();
        
        $success = true;
        foreach ($updateWorkers as $workerVersion) {
            $transaction = $db->beginTransaction();
            try {
                echo Yii::t('update', 'Updating to version {version}.', array('{version}' => $workerVersion)) . "\n";
                $this->runWorker($workerVersion);
                echo Yii::t('update', 'Updated to version {version} successfully.', array('{version}' => $workerVersion)) . "\n";
                
                $options->set('system.common.version', $versionInFile);
                $options->set('system.common.version_update.current_version', $versionInFile);
                $transaction->commit();
            } catch (Exception $e) {
                $success = false;
                $transaction->rollBack();
                echo Yii::t('update', 'Updating to version {version} failed with: {message}', array(
                    '{version}' => $workerVersion, 
                    '{message}' => $e->getMessage()
                )) . "\n";
                break;
            }
        }
        
        $db->createCommand('SET SQL_MODE=@OLD_SQL_MODE')->execute();
        $db->createCommand('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS')->execute();
        $db->createCommand('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS')->execute();
        
        if (!$success) {
            exit(1);
        }
        
        // clean directories of old asset files.
        $cleanDirectories = array(
            Yii::getPathOfAlias('common.runtime.cache'),
            Yii::getPathOfAlias('root.backend.assets.cache'),
            Yii::getPathOfAlias('root.customer.assets.cache'),
            Yii::getPathOfAlias('root.frontend.assets.cache'),
        );
        
        foreach ($cleanDirectories as $directory) {
            if (file_exists($directory) && is_dir($directory)) {
                FileSystemHelper::deleteDirectoryContents($directory, true, 0);
            }
        }
        
        $options->set('system.common.version', $versionInFile);
        $options->set('system.common.site_status', 'online');
        $options->set('system.common.version_update.current_version', $versionInFile);
        
        echo Yii::t('update', 'Congratulations, your application has been successfully updated to version {version}', array(
            '{version}' => $versionInFile,
        )) . "\n";
        exit(0);
    }
    
    protected function runWorker($version)
    {
        $workersPath    = Yii::getPathOfAlias('backend.components.update');
        $version        = str_replace('.', '_', $version);
        $className      = 'UpdateWorkerFor_' . $version;
        
        if (!is_file($classFile = $workersPath . '/' . $className . '.php')) {
            return false;
        }
        
        require_once $classFile;
        $instance = new $className();
        
        if ($instance instanceof UpdateWorkerAbstract) {
            $instance->run();
        }
        
        return true;
    }
}