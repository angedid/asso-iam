<?php

namespace App\Domain\Model;

class GroupMember
{
    public string $member_id;
    public string $group_id;
    public bool $is_group;
    public $member_data;

    public function __construct(string $member_id, string $group_id, bool $is_group, $member_data)
    {
        $this->member_id = $member_id;
        $this->group_id = $group_id;
        $this->is_group = $is_group;
        $this->member_data = $member_data;
    }
}
