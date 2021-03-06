<?php
/**
 * Tag Resource Model
 *
 * @copyright (c) 2015-present Bolt Softwares Pvt Ltd
 * @licence GNU Affero General Public License http://www.gnu.org/licenses/agpl-3.0.en.html
 */

App::uses('Tag', 'Model');
App::uses('Resource', 'Model');

class ItemTag extends AppModel {

/**
 * Details of use table
 *
 * @var array
 * @link http://book.cakephp.org/2.0/en/models/model-attributes.html
 */
	public $useTable = 'items_tags';

/**
 * Model behaviors
 *
 * @link http://api20.cakephp.org/class/model#
 */
	public $actsAs = ['Trackable'];

/**
 * Details of belongs to relationships
 *
 * @link http://book.cakephp.org/2.0/en/models/associations-linking-models-together.html#
 */
	public $belongsTo = [
		'Resource' => [
			'foreignId' => 'foreign_id',
		],
		'Tag' => [
			'foreignId' => 'tag_id',
		]
	];

/**
 * Get the validation rules upon context
 *
 * @param string $case (optional) The target validation case if any.
 * @return array cakephp validation rules
 */
	public static function getValidationRules($case = null) {
		$default = [
			'id' => [
				'uuid' => [
					'rule' => 'uuid',
					'required' => false,
					'allowEmpty' => true,
					'message' => __('UUID must be in correct format')
				]
			],
			'tag_id' => [
				'uuid' => [
					'rule' => 'uuid',
					'required' => true,
					'allowEmpty' => false,
					'message' => __('UUID must be in correct format')
				],
				'exist' => [
					'rule' => ['tagExists', null],
					'message' => __('The Tag provided does not exist')
				]
			],
			'foreign_model' => [
				'alphaNumeric' => [
					'rule' => '/^.{2,36}$/i',
					'required' => true,
					'allowEmpty' => false,
					'message' => __('Alphanumeric only')
				],
				'inList' => [
					'required' => true,
					'allowEmpty' => false,
					'rule' => 'validateForeignModel',
					'message' => __('Please enter a valid model name')
				]
			],
			'foreign_id' => [
				'uuid' => [
					'rule' => 'uuid',
					'required' => true,
					'allowEmpty' => false,
					'message' => __('UUID must be in correct format')
				],
				'exist' => [
					'rule' => ['itemExists', null],
					'message' => __('The resource provided does not exist')
				],
				'uniqueRelationship' => [
					'rule' => ['uniqueRelationship'],
					'message' => __('The tag and resource combination entered is a duplicate')
				]
			]
		];
		return $default;
	}

/**
 * Check if a Tag with same id exists
 * Custom validation rule
 *
 * @param array $check with 'tag_id' key set
 * @return bool
 */
	public function tagExists($check) {
		if ($check['tag_id'] == null) {
			return false;
		} else {
			$exists = $this->Tag->find('count', ['conditions' => ['Tag.id' => $check['tag_id']]]);

			return $exists > 0;
		}
	}

/**
 * Check if an item with same id exists
 * Custom validation rule
 *
 * @param array $check with foreign_id and foreign_model keys set
 * @return bool
 */
	public function itemExists($check) {
		$tr = $this->data['ItemTag'];
		if ($check['foreign_id'] == null) {
			return false;
		} else {
			$Item = ClassRegistry::init($tr['foreign_model']);
			$exists = $Item->find('count', [
				'conditions' => [$tr['foreign_model'] . '.id' => $check['foreign_id']],
				'recursive' => -1
			]);
			return $exists > 0;
		}
	}

/**
 * Check if a Tag / Item association don't already exist
 * Custom Validation Rule
 *
 * @param array $check (optional) data to validate
 * @return bool
 */
	public function uniqueRelationship($check = null) {
		if (isset($check['ItemTag'])) {
			$itemTag = $check['ItemTag'];
		} else {
			$itemTag = $this->data['ItemTag'];
		}

		$combination = [
			'ItemTag.tag_id' => $itemTag['tag_id'],
			'ItemTag.foreign_model' => $itemTag['foreign_model'],
			'ItemTag.foreign_id' => $itemTag['foreign_id']
		];
		$result = $this->find('count', ['conditions' => $combination]);
		return ($result === 0);
	}

/**
 * Check if the given foreign model is allowed
 *
 * @param string $foreignModel The foreign model key to test
 * @return bool
 */
	public function isValidForeignModel($foreignModel) {
		return in_array($foreignModel, Configure::read('ItemTag.foreignModels'));
	}

/**
 * Check if the given foreign model is allowed
 * Custom validation rule
 *
 * @param array $check the data to test
 * @return bool
 */
	public function validateForeignModel($check) {
		return $this->isValidForeignModel($check['foreign_model']);
	}

/**
 * Return the conditions to be used for a given context
 *
 * @param string $case (optional) The target case if any.
 * @param string $role name (optional)
 * @param array $data Used in find conditions (such as User.id)
 * @return array
 */
	public static function getFindConditions($case = 'view', $role = null, $data = null) {
		$conditions = [];
		switch ($case) {
			case 'ItemTag.viewByForeignModel':
				$conditions = [
					'conditions' => [
						'ItemTag.foreign_id' => $data['ItemTag']['foreign_id']
					],
					'order' => ['ItemTag.created desc']
				];
				break;
			case 'ItemTag.view':
				$conditions = [
					'conditions' => [
						'ItemTag.id' => $data['ItemTag']['id']
					]
				];
				break;
			default:
				$conditions = ['conditions' => []];
		}

		return $conditions;
	}

/**
 * Return the list of fields to be returned by a find operation in given context
 *
 * @param string $case context ex: login, activation
 * @param string $role optional user role if needed to build the options
 * @return array $fields
 * @access public
 */
	public static function getFindFields($case = 'view', $role = null) {
		$fields = ['fields' => []];
		switch ($case) {
			case 'ItemTag.view':
			case 'ItemTag.viewByForeignModel':
				$fields = [
					'fields' => [
						'ItemTag.id',
						'ItemTag.tag_id',
						'ItemTag.foreign_model',
						'ItemTag.foreign_id',
						'ItemTag.created',
						'ItemTag.created_by',
					],
					'contain' => [
						'Tag' => Tag::getFindFields('Tag.view', $role)
					]
				];
				break;
			case 'ItemTag.add':
				$fields = [
					'fields' => [
						'foreign_model',
						'foreign_id',
						'tag_id',
						'created',
						'modified',
						'created_by',
						'modified_by',
					]
				];
				break;
			case 'ItemTag.edit':
				$fields = ['fields' => ['content']];
				break;
		}

		return $fields;
	}

}
