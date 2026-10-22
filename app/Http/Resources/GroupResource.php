<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $transportLines = [];
        foreach($this->transportLines()->where('enabled', 1)->orderBy('code', 'asc')->get() as $line){
            $routes = [];

            foreach($line->routes()->where('enabled', 1)->get() as $route){
                $routes[] = [
                  'id' => $route->id,
                  'type' => $route->type,
                  'description' => $route->description
                ];
            }

            $transportLines[] = [
                'id' => $line->id,
                'code' => $line->code,
                'color' => $line->color,
                'point_1' => $line->point_description_1,
                'point_2' => $line->point_description_2,
                'routes' => $routes
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'creation_year' => (string)$this->creation_year,
            'description' => $this->description,
            'logo_path' => asset($this->image),
            'transport_lines' => $transportLines
        ];
    }
}
