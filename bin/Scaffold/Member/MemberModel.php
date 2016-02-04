<?php

/* 
 * Created by Hei
 */

class MemberModel{
    public $schema = '{
        "columns": {
            "id": {
                "type": "integer"
            },
            "username": {
                "type": "email", 
                "rule": [
                    "unique"
                ]
            }, 
            "password": {
                "type": "string"
            }, 
            "created_at": {
                "type": "date"
            }
        }
    }';
}
