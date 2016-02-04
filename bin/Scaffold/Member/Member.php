<?php

/* 
 * Created by Hei
 */
class Member extends Controller{
    public function Index(){
        $query = $this->Model->select();
        $members = $query->fetchAssoc();
        include $this->View->renderTemplate();
    }
    
    public function Create() {
        if (isset($this->request->cookie['id'])) {
            $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Index');
        }
        if ($this->request->isPut()) {
            $data = $this->params['Model']['Member'];
            $data['password'] = hash('sha256', $data['password']);
            $data['created_at'] = time();
            $member = $this->Model->create($data);
            if ($this->Model->insert($member)) {
                setcookie('id', $this->Model->lastInsertId());
                $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Index');
            }
        }
        include $this->View->renderTemplate();
    }
    
    public function Login() {
        if (isset($this->request->cookie['id'])) {
            $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Index');
        }
        $error = FALSE;
        if ($this->request->isPost()) {
            $data = $this->params['Model']['Member'];
            $username = $data['username'];
            $password = hash('sha256', $data['password']);
            $query = $this->Model->select('id')
                    ->where(':column = :value AND :column = :value', 'username', $username, 'password', $password);
            $result = $query->fetchAssoc();
            if (count($result) == 1) {
                if (setcookie('id', $result[0]['id'])) {
                    $this->View->redirect('Index');
                } else {
                    $error = 'Unable to setcookie, please check if your browser has enabled cookie';
                }
            } else {
                $error = 'Incorrect username and/or password';
            }
        }
        include $this->View->renderHTML();
    }
    
    public function Logout() {
        setcookie('id', '', 0);
        $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Index');
    }
    
    public function Edit($id) {
        $error = FALSE;
        
        if (isset($this->request->cookie['id'])) {
            if ($id != $this->request->cookie['id']) {
                $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Edit/' . $this->request->cookie['id']);
            }
        } else {
            $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Login');
        }
        $query = $this->Model->select()
                ->where(':column = :value', 'id', $id);
        $member = $query->fetchAssoc()[0];
        if ($this->request->isPut()) {
            $memberData = $this->Model->select()
                    ->where(':column = :value', 'id', $id)
                    ->fetchAssoc()[0];
            $memberRow = $this->Model->create($memberData);
            $memberRow->username = $memberData['username'];
            if ($this->params['Model']['Member']['password'] != '') {
                $memberRow->password = hash('sha256', $this->params['Model']['Member']['password']);
            } else {
                $memberRow->password = $member['password'];
            }
            
            $success = TRUE;
            if (!$this->Model->edit($memberRow)) { 
                $success = FALSE;
                $error = $this->Model->lastError;
                $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/');
            }
        }
        include $this->View->renderHTML();
    }
    
    public function Show($id){
        $query = $this->Model->select('*')
                ->where(':column = :value', 'id', $id);
        $member = $query->fetchAssoc()[0];
        include $this->View->renderTemplate();
    }
    
    public function Destroy($id) {
        if (isset($this->request->cookie['id'])) {
            if ($id != $this->request->cookie['id']) {
                $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Destroy/' . $this->request->cookie['id']);
            }
        } else {
            $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Login');
        }
        
        if ($this->Model->destroy($id)) {
            $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Logout');
        }
        $this->View->redirect(SIMPHPFY_RELATIVE_PATH . 'Member/Index');
    }
}
