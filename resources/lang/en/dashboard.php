<?php

return [
    'common' => [
        'view_all' => 'View all',
        'pending' => 'Pending',
        'participant_count' => '{1} 1 participant|[2,*] :count participants',
    ],

    'organization' => [
        'users_heading' => 'Members',
        'users_subtitle' => ':count people in this organization',
        'users_empty_subtitle' => 'No members yet',
        'users_empty_body' => 'Invited colleagues appear here once they accept.',

        'projects_heading' => 'Projects',
        'projects_subtitle' => '{1} 1 project|[2,*] :count projects',
        'projects_empty_subtitle' => 'No projects yet',
        'projects_empty_body' => 'A project holds its own participants, events and travel expenses.',
        'projects_no_participants' => 'No participants yet',
        'projects_new' => 'New project',

        'activity_heading' => 'Activity',
        'activity_subtitle' => 'Latest 5 events in this organization',
        'activity_empty_subtitle' => 'No activity yet',
        'activity_empty_body' => 'Changes to projects, participants and events are logged here.',
    ],

    'project' => [
        'members_heading' => 'Members',
        'members_subtitle' => ':count people with access to this project',
        'members_empty_subtitle' => 'No members yet',
        'members_empty_body' => 'People from the organization appear here once they are given access.',

        'participants_heading' => 'Participants',
        'participants_subtitle' => '{1} 1 participant on this project|[2,*] :count participants on this project',
        'participants_empty_subtitle' => 'No participants yet',
        'participants_empty_body' => 'Participants you add to this project appear here.',

        'travel_expenses_heading' => 'Travel expenses',
        'travel_expenses_subtitle' => '{1} 1 participant travel expenses registered|[2,*] :count participants travel expenses registered',
        'travel_expenses_empty_subtitle' => 'No travel expenses yet',
        'travel_expenses_empty_body' => 'Claims logged against this project are summed here per participant.',
        'travel_expenses_sum' => 'Total',

        'calendar_heading' => 'Calendar',

        'activity_heading' => 'Activity',
        'activity_subtitle' => 'Latest 5 events on this project',
        'activity_empty_subtitle' => 'No activity yet',
        'activity_empty_body' => 'Changes to this project are logged here.',
    ],
];
