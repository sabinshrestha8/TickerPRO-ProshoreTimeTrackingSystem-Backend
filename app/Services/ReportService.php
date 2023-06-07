<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    protected User $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }


    /**
     * @param array $validated
     * @return object
     */
    public function getUsersReport(array $validated): object
    {
        $users = $this->user
            ->whereIn('id', Arr::get($validated, 'user_id'))
            ->with([
                'timelogs' => function ($query) use ($validated) {
                    $query
                        ->whereBetween('start_date', [Arr::get($validated, 'start_date'), Arr::get($validated, 'end_date')])
                        ->whereBetween('end_date', [Arr::get($validated, 'start_date'), Arr::get($validated, 'end_date')])
                        ->where('billable', 0)
                        ->whereIn('project_id', Arr::get($validated, 'project_id'))
                        ->whereHas('project', function ($query) {
                            $query->where('billable', 1);
                        });
                }
            ])->get();


        $report = $this->getUsersReportDetails($validated, $users);

        return $report;
    }


    /**
     * @param array $validated
     * @param object $users
     * @return object
     */
    public function getUsersReportDetails(array $validated, object $users): object
    {
        $reports = $users->map(function ($user) use ($validated) {
            $userTotalTime = $user->timelogs->sum(function ($timelog) {
                $startDateTime = Carbon::parse($timelog->start_date . ' ' . $timelog->started_time);
                $endDateTime = Carbon::parse($timelog->end_date . ' ' . $timelog->ended_time);
                return $endDateTime->diffInMinutes($startDateTime);
            });
            $activities = $this->getUserActivity($validated, $user);
            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'total_time' => intdiv($userTotalTime, 60) . 'hrs ' . ($userTotalTime % 60) . 'min',
                'activities' => $activities
            ];
        });

        return $reports;
    }


    /**
     * @param array $validated
     * @param object $user
     * @return object
     */
    public function getUserActivity(array $validated, object $user): object
    {
        $activities = $user->timelogs->map(function ($timelog) use ($validated) {
            // dd($timelog->project->client->client_name);
            $startDateTime = Carbon::parse($timelog->start_date . ' ' . $timelog->started_time);
            $endDateTime = Carbon::parse($timelog->end_date . ' ' . $timelog->ended_time);
            $totalTime = $endDateTime->diffInMinutes($startDateTime);
            $activity = [
                'activity' => $timelog->activity_name,
                'total_time' =>  intdiv($totalTime, 60) . 'hrs ' . ($totalTime % 60) . 'min',
                'project' => $timelog->project->project_name,
                'client' => $timelog->project->client->client_name,
                'date' => Carbon::parse($startDateTime)->format('M j')
            ];
            return $activity;
        });

        return $activities;
    }

    /**
     *
     * @param object $reports
     * @param string $startDate
     * @param string $endDate
     */
    public function generatePdfReport(object $reports, string $startDate, string $endDate)
    {
        $start_date = Carbon::parse($startDate)->toFormattedDateString();
        $end_date = Carbon::parse($endDate)->toFormattedDateString();

        $pdf = Pdf::loadView('reports.reportPdf',  compact(['reports', 'start_date', 'end_date']));
        return $pdf->stream('report.pdf');
    }
}
