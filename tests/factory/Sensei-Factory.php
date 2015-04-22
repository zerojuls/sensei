<?php
/**
 * Class Sensei Factory
 *
 * This class takes care of creating testing data for the Sensei Unit tests
 *
 * @since 1.7.4
 */
class Sensei_Factory extends  WP_UnitTest_Factory{

    /**
     * All the lessons ids generated by this factory
     * @var $lesson_ids
     */
    protected $lesson_ids;

    /**
     * All the lessons ids generated by this factory
     * @var $lesson_ids
     */
    protected $question_ids;

    /**
     * constructor function
     *
     * This sets up some basic demo data
     */
    public function __construct(){
        // construct the parent
        parent::__construct();

        // generate sample lessons
        $this->lesson_ids = $this->generate_test_lessons();
        // generate lesson questions
        foreach( $this->lesson_ids as $lesson_id ){

            $this->attach_lessons_questions( 12 , $lesson_id );

        }

    }// end construct

    /**
     * Accesses the test_data lesson_id's and return any one of them
     *
     * @since 1.7.2
     *
     * @param int $number_of_items optional, defaults to 1
     *
     * @return int | array $result. If number of items is greater than one, this function will return an array
     */
    public function get_random_lesson_id( $number_of_items = 1 ){

        if( $number_of_items> 1 ){

            $result = array();
            $random_index_s = array_rand( $this->lesson_ids, $number_of_items );
            foreach( $random_index_s as $index ){
                array_push( $result, $this->lesson_ids[ $index ] );
            }// end for each

        }else{

            $random_index = array_rand( $this->lesson_ids );
            $result = $this->lesson_ids[ $random_index ];

        }

        return $result;

    } // end get_random_valid_lesson_id()

    /**
     * generate random lessons
     *
     * @param int $number how many lessons would you like to generate. Default 10.
     * @return array $lesson_ids
     */
    protected function generate_test_lessons( $number = 10  ){

        $lesson_ids = array();

        // create random $number of test lessons needed in the class tests
        foreach (range( 0, $number ) as $count ) {

            $new_lesson_args = array(
                'post_content' => 'lesson ' . ( $count + 1 ) . ' test content',
                'post_name' => 'test-lesson ' . ( $count + 1 ) ,
                'post_title' => 'test-lesson ' . ( $count + 1 ) ,
                'post_status' => 'publish',
                'post_type' => 'lesson'
            );
            // add the lesson id to the array of ids
            $lesson_ids[ $count ] = wp_insert_post( $new_lesson_args );

        } // end for each range 0 to 12

        return $lesson_ids;

    }// end generate_test_lessons

    /**
     * This function creates dummy answers for the user based on the quiz questions for the
     * quiz id that is passed in.
     *
     * @since 1.7.2
     * @access public
     *
     * @param int $quiz_id
     * @returns array $user_quiz_answers
     */
    public function generate_user_quiz_answers( $quiz_id ){

        global $woothemes_sensei;
        $user_quiz_answers =  array();

        if( empty( $quiz_id ) ||  'quiz' != get_post_type( $quiz_id ) ){

            return $user_quiz_answers;

        }

        // get all the quiz questions that is added to the passed in quiz
        $quiz_question_posts = $woothemes_sensei->lesson->lesson_quiz_questions( $quiz_id );

        if( empty( $quiz_question_posts ) || count( $quiz_question_posts ) == 0
            || ! isset(  $quiz_question_posts[0]->ID ) ){

            return $user_quiz_answers;

        }

        // loop through all the question and generate random answer data
        foreach( $quiz_question_posts as $question ){

            // get the current question type
            $question_types_array = wp_get_post_terms( $question->ID, 'question-type', array( 'fields' => 'slugs' ) );

            if ( isset( $question_types_array[0] ) && '' != $question_types_array[0] ) {
                $type = $question_types_array[0];
            }else{
                $type = 'multiple-choice';
            }

            // setup the demo data and store it in the respective array
            if ('multiple-choice' == $type ) {
                // these answer can be found the question generate and attach answers function
                $question_meta = get_post_meta( $question->ID );
                $user_quiz_answers[ $question->ID ] = array( 0 => 'wrong1'.rand() );

            } elseif ('boolean' == $type ) {

                $bool_answer = 'false';
                $random_is_1 = rand(0,1);

                if( $random_is_1 ){
                    $bool_answer = 'true';
                }

                $user_quiz_answers[ $question->ID ] = $bool_answer;

            } elseif ( 'single-line' == $type  ) {

                $user_quiz_answers[ $question->ID ] = 'Single line answer for basic testing '. rand() ;

            } elseif ( 'gap-fill' == $type ) {

                $user_quiz_answers[ $question->ID ] = 'OneWordScentencesForSampleAnswer '.rand();

            } elseif ( 'multi-line' == $type  ) {

                $user_quiz_answers[ $question->ID ] = 'Sample paragraph to test the answer '. rand();

            } elseif ( 'file-upload' == $type ) {

                $user_quiz_answers[ $question->ID ] = '';

            }

        }// end for quiz_question_posts

        return $user_quiz_answers;

    }// end generate_user_quiz_answers()

    /**
     * Generate an array of user quiz grades
     *
     * @param int $number number of questions to generate. Default 10
     * @trows new 'Generate questions needs a valid lesson ID.' if the ID passed in is not a valid lesson
     */
    public function generate_user_quiz_grades( $quiz_answers ){

        if( empty( $quiz_answers ) || ! is_array( $quiz_answers )  ){
            throw new Exception(' The generate_user_quiz_grades parameter must be a valid array ');
        }

        $quiz_grades = array();
        foreach( $quiz_answers as $question_id => $answer  ){

            $quiz_grades[ $question_id ] = rand( 1 , 5);

        }//  end foreach

        return $quiz_grades;

    }// generate_user_quiz_grades

    /**
     * Generate and attach lesson questions.
     *
     * This will create a set of questions. These set of questions will be added to every lesson.
     * So all lessons the makes use of this function will have the same set of questions in their
     * quiz.
     *
     * @param int $number number of questions to generate. Default 10
     * @param int $lesson_id
     * @throws Exception
     * @trows new 'Generate questions needs a valid lesson ID.' if the ID passed in is not a valid lesson
     */
    protected function attach_lessons_questions( $number = 10 , $lesson_id ){

        global $woothemes_sensei;

        if( empty( $lesson_id ) || ! intval( $lesson_id ) > 0
            || ! get_post( $lesson_id ) ||  'lesson'!= get_post_type( $lesson_id )  ){
            throw new Exception('Generate questions needs a valid lesson ID.');
        }

        // create a new quiz and attach it to the lesson
        $new_quiz_args = array(
            'post_type' => 'quiz',
            'post_name' => 'lesson_id_ ' .  $lesson_id . '_quiz' ,
            'post_title' => 'lesson_id_ ' .  $lesson_id . '_quiz' ,
            'post_status' => 'publish',
            'post_parent' => $lesson_id

        );

        $quiz_id = wp_insert_post( $new_quiz_args );

        // setup quiz meta
        update_post_meta( $quiz_id, '_quiz_grade_type', 'manual' );
        update_post_meta( $quiz_id, '_pass_required', 'on' );
        update_post_meta( $quiz_id, '_quiz_passmark' , 50 );


        // if the database already contains questions don't create more but add
        // the existing questions to the passed in lesson id's lesson
        $question_post_query = new WP_Query( array( 'post_type' => 'question' ) );
        $questions = $question_post_query->get_posts();

        if( ! count( $questions ) > 0 ){

            // generate questions if none exists
            $questions = $this->generate_questions( $number );

            // create random $number of question   lessons needed in the class tests
            foreach ( $questions as $question ) {

                $question[ 'quiz_id' ] = $quiz_id;
                $question[ 'post_author'] = get_post( $quiz_id )->post_author;
                $woothemes_sensei->lesson->lesson_save_question( $question );

            } // end for each range 0 to 12



        } else {

            // simply add questions to incoming lesson id

            foreach ( $questions as $index => $question  ) {

                // Add to quiz
                add_post_meta( $question->ID, '_quiz_id', $quiz_id , false );

                // Set order of question
                $question_order = $quiz_id . '000' . $index;
                add_post_meta( $question->ID, '_quiz_question_order' . $quiz_id , $question_order );

            }
        } // end if count

        return;
    }

    /**
     * Generates questions from each question type with the correct data and then attaches that to the quiz
     *
     * @param int $number the amount of questions we want to attach defaults to 10
     * @return array $questions
     */
    protected function generate_questions( $number = 10 ){

        global $woothemes_sensei;
        $chosen_questions =  array(); // will be used to store generated question
        $sample_questions = array(); // will be used to store 1 sample from each question type

        // get all allowed question data
        //'multiple-choice' 'boolean' 'gap-fill' 'single-line' 'multi-line' 'file-upload'
        $question_types = $woothemes_sensei->question->question_types();

        // get the question type slug as this is used to determine the slug and not the string type
        $question_type_slugs = array_keys($question_types);

        // generate ten random-ish questions
        foreach( range( 0, ( $number - 1 ) )  as $count ) {

            //make sure that at least on question from each type is included
            if( $count < ( count( $question_types ) )  ){

                //setup the question type at the current index
                $type =  $question_type_slugs[ $count ];

            }else{

                // setup a random question type
                $random_index = rand( 0, count( $question_types ) - 1 );
                $type =  $question_type_slugs[$random_index];

            }

            $test_question_data = array(
                'question_type' => $type ,
                'question_category' => 'undefined' ,
                'action' => 'add',
                'question' => 'Is this a sample' . $type  . ' question ? _ ' . rand() ,
                'question_grade' => '1' ,
                'answer_feedback' => 'Answer Feedback sample ' . rand() ,
                'question_description' => ' Basic description for the question' ,
                'question_media' => '' ,
                'answer_order' => '' ,
                'random_order' => 'yes' ,
                'question_count' => $number
            );

            // setup the right / wrong answers base on the question type
            if ('multiple-choice' == $type ) {

                $test_question_data['question_right_answers'] = array( 'right' ) ;
                $test_question_data['question_wrong_answers'] = array( 'wrong1', 'wrong2',  'wrong3' )  ;

            } elseif ('boolean' == $type ) {

                $test_question_data[ 'question_right_answer_boolean' ] = true;

            } elseif ( 'single-line' == $type  ) {

                $test_question_data[ 'add_question_right_answer_singleline' ] = '';

            } elseif ( 'gap-fill' == $type ) {

                $test_question_data[ 'add_question_right_answer_gapfill_pre' ] = '';
                $test_question_data[ 'add_question_right_answer_gapfill_gap' ] = '';
                $test_question_data[ 'add_question_right_answer_gapfill_post'] = '';

            } elseif ( 'multi-line' == $type  ) {

                $test_question_data [ 'add_question_right_answer_multiline' ] = '';

            } elseif ( 'file-upload' == $type ) {

                $test_question_data [ 'add_question_right_answer_fileupload'] = '';
                $test_question_data [ 'add_question_wrong_answer_fileupload' ] = '';

            }

            $sample_questions[] = $test_question_data;
        }

        // create the requested number tests from the sample questions
        foreach( range( 1 , $number ) as $count ){

            // get the available question types
            $available_question_types = count( $sample_questions);
            $highest_question_type_array_index = $available_question_types-1;

            //select a random question
            $random_index = rand( 0, $highest_question_type_array_index  );
            $randomly_chosen_question = $sample_questions[ $random_index ];

            // attache the chosen question to be returned
            $chosen_questions[] = $randomly_chosen_question;
        }

        return $chosen_questions;

    }// end generate_and_attach_questions

    /**
     * This functions take answers submitted by a user, extracts ones that is of type file-upload
     * and then creates and array of test $_FILES
     *
     * @param array $test_user_quiz_answers
     * @return array $files
     */
    public function generate_test_files( $test_user_quiz_answers ){

        $files = array();
        //check if there are any file-upload question types and generate the dummy file data
        foreach( $test_user_quiz_answers as $question_id => $answer ){

            //Setup the question types
            $question_types = wp_get_post_terms( $question_id, 'question-type' );
            foreach( $question_types as $type ) {
                $question_type = $type->slug;
            }

            if( 'file-upload' == $question_type){
                //setup the sample image file location within the test folders
                $test_images_directory = dirname( dirname( __FILE__) ) . '/images/';

                // make a copy of the file intended for upload as
                // it will be moved to the new location during the upload
                // and no longer available for the next test
                $new_test_image_name = 'test-question-' . $question_id . '-greenapple.jpg';
                $new_test_image_location = $test_images_directory . $new_test_image_name  ;
                copy ( $test_images_directory . 'greenapple.jpg', $new_test_image_location   );

                $file = array(
                    'name' => $new_test_image_name,
                    'type' => 'image/jpeg' ,
                    'tmp_name' => $new_test_image_location ,
                    'error' => 0,
                    'size' => 4576 );

                // pop the file on top of the car
                $files[ 'file_upload_' . $question_id ] = $file;
            }

        } // end for each $test_user_quiz_answers

        return $files;

    }// end generate_test_files()

    /**
     * Returns a random none file question id from the given user input array
     *
     * @since 1.7.4
     * @param array $user_answers
     *
     * @return int $index
     */
    public function get_random_none_file_question_index( $user_answers  ){

        if( empty( $user_answers )
            || ! is_array( $user_answers ) ){

            return false;

        }

        global $woothemes_sensei;
        // create a new array without questions of type file
        $answers_without_files = array();
        foreach( $user_answers as $question_id => $answer  ){

          $type  = $woothemes_sensei->question->get_question_type( $question_id );

          if( 'file-upload' !=  $type  ){
              $answers_without_files[ $question_id ] = $answer;
          }
        }// end foreach

        $index = array_rand( $answers_without_files );
        return $index;
    }// end get_random_none_file_question_index


    /**
     * Returns a random file question id from the given user input array
     *
     * @since 1.7.4
     * @param array $user_answers
     *
     * @return int $index
     */
    public function get_random_file_question_index( $user_answers  ){

        if( empty( $user_answers )
            || ! is_array( $user_answers ) ){

            return false;

        }

        global $woothemes_sensei;
        // create a new array without questions of type file
        $file_type_answers = array();
        foreach( $user_answers as $question_id => $answer  ){

            $type  = $woothemes_sensei->question->get_question_type( $question_id );

            if( 'file-upload' ==  $type  ){
                $file_type_answers[ $question_id ] = $answer;
            }
        }// end foreach

        $index = array_rand( $file_type_answers );
        return $index;
    }// end get_random_none_file_question_index


    /**
     * This function creates dummy answers for the user based on the quiz questions for the
     * quiz id that is passed in.
     *
     * @since 1.7.2
     * @access public
     *
     * @param int $quiz_id
     * @returns array $user_quiz_answers
     */
    public function generate_user_answers_feedback( $quiz_id ){

        global $woothemes_sensei;
        $answers_feedback =  array();

        if( empty( $quiz_id ) ||  'quiz' != get_post_type( $quiz_id ) ){

            return $answers_feedback;

        }

        $answers = $this->generate_user_quiz_answers( $quiz_id );

        foreach( $answers as $question_id => $answer ){

            $answers_feedback[ $question_id ] = 'Sample Feedback '. rand();

        }

        return $answers_feedback;

    } // end generate_user_answers_feedback

}// end Sensei Factory class