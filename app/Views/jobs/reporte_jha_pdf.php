<?php
	// escape user-entered text so it can't break the HTML/table structure TCPDF parses
	$esc = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	$escNl = fn($value) => nl2br($esc($value));

	// create some HTML content
	$html = '<br><h2 align="center" style="color:#337ab7;">JOB HAZARDS ANALYSIS<br><br></h2>
				<style>
				table {
					font-family: arial, sans-serif;
					border-collapse: collapse;
					width: 100%;
				}

				td, th {
					border: 1px solid #dddddd;
					text-align: left;
					padding: 8px;
				}
				</style>
			<table border="0" cellspacing="0" cellpadding="5">
				<tr>
					<th bgcolor="#337ab7" style="color:white;"><strong>Job Code/Name: </strong></th>
					<th colspan="3">' . $esc(strtoupper($info[0]['job_description'])). '</th>
				</tr>

				<tr>
					<th bgcolor="#337ab7" style="color:white;"><strong>Done by: </strong></th>
					<th>' . $esc($info[0]['name']). '</th>
					<th bgcolor="#337ab7" style="color:white;"><strong>Date & Time: </strong></th>
					<th>' . $esc($info[0]['date_log']). '</th>
				</tr>

				<tr>
					<th bgcolor="#337ab7" style="color:white;"><strong>Observation: </strong></th>
					<th colspan="3">' . $escNl($info[0]['observation']). '</th>
				</tr>

			</table>';
								
				$html.= '<br><br>';
				
				$html .= '<table border="1" cellspacing="0" cellpadding="5">
						<tr>
							<th colspan="5"><strong><i>Identify and prioritize hazards below, then identify plans to eliminate/control the hazards</i></strong></th>
						</tr>
						<tr bgcolor="#337ab7" style="color:white;">
							<th width="5%" align="center"><strong>#</strong></th>
							<th width="25%" align="center"><strong>Activity</strong></th>
							<th width="20%" align="center"><strong>Hazard</strong></th>
							<th width="10%" align="center"><strong>Priority</strong></th>
							<th width="40%" align="center"><strong>Control/Eliminate</strong></th>
						</tr>';
					if(!$hazards){
							$html.= '<tr>';
							$html.= '<th colspan="5" align="center"> ---- No data was found for Hazard -----</th>';
							$html.= '</tr>';					
					}else{
						$i = 0;
						foreach ($hazards as $data):
							$i++;
							$html.= '<tr>';
							$html.= '<th align="center">' . $i . '</th>';
							$html .= '<th >' . $escNl($data['hazard_activity']) . '</th>';
							$html.= '<th >' . $escNl($data['hazard_description']) . '</th>';
							$priority = $data['priority_description']==""?"-":$data['priority_description'];
							$html.= '<th align="center">' . $esc($priority) . '</th>';
							$html.= '<th >' . $escNl($data['solution'])  . '</th>';
							$html.= '</tr>';
						endforeach;
					}
				$html.= "</table><br><br><br><br>";
			

echo $html;
						
?>