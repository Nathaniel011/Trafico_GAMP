<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $children = [];
        foreach($this->children as $child){
            $children[] = [
                'id' => $child->id,
                'name' => $child->name,
                'logo_path' => $child->logo_path,
            ];
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_path' => $this->logo_path,
            'children' =>$children
        ];
    }
}
