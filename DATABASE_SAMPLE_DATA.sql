SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

INSERT INTO `mmpusers` (`uid`, `email`, `username`, `picture`, `initial`, `created`) VALUES
(1, 'freesewing@mailinator.com', 'example-user', '{"filename":"picture-1-1415241878.jpg","uri":"public://user-pictures/picture-1-1415241878.jpg","filemime":"image/jpeg","filesize":24875}', 'freesewing@mailinator.com', 1390237847);

INSERT INTO `mmpmodels` (`modelid`, `uid`, `title`, `sex`, `picture`, `data`) VALUES
(1043, 1, 'Test decimal', 0, '', '{"field_across_back_width":123.5,"field_biceps_circumference":123.5,"field_body_rise":123.5,"field_center_back_neck_to_waist":123.5,"field_chest":123.45,"field_corset_hip_circumference":123.45,"field_hood_opening":123.5,"field_inseam":123.5,"field_natural_waist":123.45,"field_natural_waist_to_corset_hi":123.45,"field_natural_waist_to_hip":123.5,"field_natural_waist_to_underbust":123.45,"field_neck_size":123.45,"field_seat_circumference":123.5,"field_shoulder_to_elbow_length":123.5,"field_sleeve_length":123.5,"field_neck_to_waist":123.45,"field_trouser_waist_circum":123.5,"field_underbust":123.45,"field_uppermost_leg_circumferenc":123.5,"field_waistline_to_waistline_cro":123.5}'),
(55, 1, 'Joost De Cock', 0, '{"filename":"11147763864_e78f5775d4_o.jpg","uri":"public://models/11147763864_e78f5775d4_o.jpg","filemime":"image/jpeg","filesize":65161}', '{"field_across_back_width":45,"field_biceps_circumference":33.5,"field_body_rise":20,"field_center_back_neck_to_waist":48,"field_chest":108,"field_hood_opening":81,"field_inseam":91,"field_natural_waist":88.5,"field_natural_waist_to_hip":26,"field_natural_waist_to_trouser_w":12,"field_neck_size":42,"field_seat_circumference":104.5,"field_shoulder_length":16,"field_shoulder_slope":5.5,"field_shoulder_to_elbow_length":41,"field_sleeve_length":73,"field_neck_to_waist":50,"field_trouser_waist_circum":95,"field_uppermost_leg_circumferenc":60,"field_waistline_to_waistline_cro":67,"field_wrist_circumference":19}'),
(255, 1, 'Timothy standard model', 0, '', '{"field_body_rise":28.1,"field_inseam":80,"field_seat_circumference":102,"field_trouser_waist_circum":90}'),
(56, 1, 'Sorcha Ni Dhubhghaill', 1, '{"filename":"14863319311_7614e8774d_k.jpg","uri":"public://models/14863319311_7614e8774d_k.jpg","filemime":"image/jpeg","filesize":45971}', '{"field_corset_hip_circumference":100,"field_natural_waist":84,"field_natural_waist_to_corset_hi":20,"field_natural_waist_to_underbust":9,"field_underbust":73}'),
(1882, 1, 'Mauricio', 0, '', '{"field_seat_circumference":92,"field_trouser_waist_circum":84,"field_uppermost_leg_circumferenc":49,"field_waistline_to_waistline_cro":64}'),
(1059, 1, 'Sample new form', 0, '{"filename":"14614382874_d253f152f1_o.png","uri":"public://models/14614382874_d253f152f1_o.png","filemime":"image/png","filesize":326096}', '{"field_trouser_waist_circum":12,"field_uppermost_leg_circumferenc":13,"field_waistline_to_waistline_cro":14}'),
(1060, 1, 'Test new form', 0, '', '{"field_trouser_waist_circum":12}'),
(84, 1, 'Mr. Hanne', 0, '', '{"field_trouser_waist_circum":82,"field_uppermost_leg_circumferenc":53.5,"field_waistline_to_waistline_cro":62}');

