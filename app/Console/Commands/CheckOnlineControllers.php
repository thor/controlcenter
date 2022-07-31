<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Area;
use App\Models\Position;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InactiveOnlineNotification;
use App\Notifications\InactiveOnlineStaffNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use anlutro\LaravelSettings\Facade as Setting;

class CheckOnlineControllers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:controllers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors the online controllers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info("Starting online controller check...");

        // Check if the setting is turned on
        if(!Setting::get('atcActivityNotifyInactive')){
            return;
        }

        // Fetch which country ICAOs we should look for based on positions database
        $areasRaw = DB::select(
            DB::raw('SELECT DISTINCT LEFT(callsign, 2) as prefix FROM positions;')
        );

        $areas = collect();
        foreach($areasRaw as $a){
            $areas->push($a->prefix);
        }

        $areasRegex = "/(^".$areas->implode('|^').")\w+/";

        $this->info("Collecting online controllers...");

        // Fetch the latest URI to data feed
        $dataUri = json_decode(file_get_contents('https://status.vatsim.net/status.json'))->data->v3[0];
        $vatsimData = json_decode(file_get_contents($dataUri))->controllers;

        foreach($vatsimData as $d){
            if(preg_match($areasRegex, $d->callsign)){
                // Lets check this user
                $user = User::find($d->cid);
                if(isset($user)){
                    if(!$user->active && !$user->hasActiveTrainings() && !$user->isVisiting()){
                        if(!isset($user->last_inactivity_warning) || (isset($user->last_inactivity_warning) && Carbon::now()->gt(Carbon::parse($user->last_inactivity_warning)->addHours(6)))){
                            // Send warning to user
                            //$user->notify(new InactiveOnlineNotification($user));
                            $this->info($user->name.' is inactive');
                            $user->last_inactivity_warning = now();
                            $user->save();

                            // Send warning to all admins, and moderators in selected area
                            $position = Position::where('callsign', $d->callsign)->get()->first();
                            $sendToStaff = User::allWithGroup(1);
                            $moderators = User::allWithGroup(2);
                            foreach($moderators as $m){
                                if(!$m->isModerator(Area::find($position->area->id))){
                                    $sendToStaff->push($m);
                                }
                            }
                            $this->info('sending to staff: '.$sendToStaff->implode('name', ', '));
                            //Notification::send($sendToStaff, new InactiveOnlineStaffNotification($user, $d->callsign, $d->logon_time));
                        }
                    }
                }
            }
        }
        
        return 0;
    }
}
