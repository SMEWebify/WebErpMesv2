<?php

namespace Tests\Unit\Cad;

/**
 * Smallest BySoft .bvc the import reads, as the bytes of the archive.
 */
class BvcFixture
{
    /**
     * A job of one part (100x50, one hole of radius 10, micro-joint on the
     * exterior) ordered 6 times, over two nests, the second cut twice.
     */
    public static function archive(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bvc');

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('JobInfo.xml', self::jobInfo());
        $zip->addFromString('JOB-01.ccp', self::ccp());
        $zip->addFromString('JOB-02.ccp', self::ccp());
        $zip->close();

        try {
            return (string) file_get_contents($path);
        } finally {
            unlink($path);
        }
    }

    private static function jobInfo(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<JobInfo Name="JOB" Material="S235" Thickness="10" MeasurementSystem="Metric" ProcessGas="O2">
  <PartInfos>
    <PartInfo Id="1" Name="P1-nb6" Quantity="6" PartRefIds="1,2,3,4,5,6" />
    <PartInfo Id="0" Name="Dummy" Quantity="1" />
  </PartInfos>
  <MaterialInfos>
    <MaterialInfo Id="1" Quantity="3" SizeX="3000" SizeY="1500" FormatType="Sheet" />
  </MaterialInfos>
  <PlanInfos>
    <PlanInfo Multiplicity="1" Name="JOB-01" MaterialId="1" EstimatedCuttingTime="120" SizeX="110" SizeY="70" Waste="0.9">
      <PlanPartInfos>
        <PlanPartInfo PartId="1" Quantity="2" SubProgramName="Sub1" />
        <PlanPartInfo PartId="0" Quantity="1" SubProgramName="ResidualSheetCuts_2" />
      </PlanPartInfos>
      <ResidualSheetInfos>
        <ResidualSheetInfo SizeX="2700" SizeY="1500" Bottom="0" Left="300" />
      </ResidualSheetInfos>
    </PlanInfo>
    <PlanInfo Multiplicity="2" Name="JOB-02" MaterialId="1" EstimatedCuttingTime="120" SizeX="110" SizeY="70" Waste="0.9">
      <PlanPartInfos>
        <PlanPartInfo PartId="1" Quantity="2" SubProgramName="Sub1" />
        <PlanPartInfo PartId="0" Quantity="1" SubProgramName="ResidualSheetCuts_2" />
      </PlanPartInfos>
      <ResidualSheetInfos />
    </PlanInfo>
  </PlanInfos>
  <EstimatedCuttingTime>360</EstimatedCuttingTime>
</JobInfo>
XML;
    }

    private static function ccp(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<CuttingControlProgram MeasurementSystem="Metric" Thickness="10">
  <ApplicationFlat PlanWidth="110" PlanHeight="70" SheetWidth="3000" SheetHeight="1500" />
  <SubPrograms>
    <SubProgram Id="1" Name="Sub1" PartNumber="1" PartWidth="105" PartHeight="50">
      <ControlCommands>
        <SetOperationMode Id="2" Value="0" />
        <MoveLineTo Id="3" X="50" Y="25" />
        <BeginLeadIn Id="4" />
        <ExecPiercing Id="5" />
        <LineTo Id="6" X="60" Y="25" />
        <EndLeadIn Id="7" />
        <BeginCutOut Id="8" Contour="Interior" />
        <ArcTo Id="9" X="40" Y="25" SweepAngle="180" />
        <ArcTo Id="10" X="60" Y="25" SweepAngle="180" />
        <EndCutOut Id="11" />
        <MoveLineTo Id="12" X="-5" Y="10" />
        <BeginLeadIn Id="13" />
        <ExecPiercing Id="14" />
        <LineTo Id="15" X="0" Y="10" />
        <EndLeadIn Id="16" />
        <BeginCutOut Id="17" Contour="Exterior" />
        <LineTo Id="18" X="0" Y="0" />
        <LineTo Id="19" X="100" Y="0" />
        <LineTo Id="20" X="100" Y="50" />
        <LineTo Id="21" X="0" Y="50" />
        <LineTo Id="22" X="0" Y="10.5" />
        <ExecMicrojoint Id="23" />
        <EndCutOut Id="24" />
      </ControlCommands>
    </SubProgram>
    <SubProgram Id="25" Name="ResidualSheetCuts_2" PartHeight="1500">
      <ControlCommands>
        <MoveLineTo Id="26" X="0" Y="0" />
        <BeginCutOut Id="27" Contour="Exterior" />
        <LineTo Id="28" X="0" Y="1500" />
        <EndCutOut Id="29" />
      </ControlCommands>
    </SubProgram>
  </SubPrograms>
  <ControlCommands>
    <CallSubProgram Id="30" X="10" Y="10" Rotation="-0" SubProgramName="Sub1" PartRefIds="1" />
    <CallSubProgram Id="31" X="110" Y="70" Rotation="180" SubProgramName="Sub1" PartRefIds="2" />
    <CallSubProgram Id="32" X="300" Y="0" Rotation="-0" SubProgramName="ResidualSheetCuts_2" />
  </ControlCommands>
</CuttingControlProgram>
XML;
    }
}
