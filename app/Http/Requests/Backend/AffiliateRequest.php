<?php

namespace App\Http\Requests\Backend;

use App\Http\Requests\Request;

/**
 * Class ManageRoleRequest
 * @package App\Http\Requests\Backend\Access\Role
 */
class AffiliateRequest extends Request
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		return [
			'name' => 'required',
			'city' => 'required',
			'state' => 'required'
		];
	}
}